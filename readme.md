<p align="center"><img src="https://laravel.com/assets/img/components/logo-laravel.svg"></p>

<p align="center">
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/license.svg" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable, creative experience to be truly fulfilling. Laravel attempts to take the pain out of development by easing common tasks used in the majority of web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

1. Install PayPal SDK and Laracast Flash notification via composer

Edit File composer.json and add

<pre>
"require": {
"laracasts/flash": "^2.0",
"paypal/rest-api-sdk-php": "*"
}
</pre>

Now, include the service provider within <b>config/app.php</b>

<pre>
'providers' => [
Laracasts\Flash\FlashServiceProvider::class,
];

'aliases' => [
'Flash' => Laracasts\Flash\Flash::class,
];
</pre>

2. Create Paypal config file

create paypal config file in config directory with name paypal :-
<b>config/paypal.php</b>

<pre>
return array(
// set your paypal credential
// Below credentials are different for sandbox mode and live mode.
'client_id' => 'Your Paypal Client ID will be here',
'secret' => 'Your Paypal secret key will be here',

/**
* SDK configuration
*/
'settings' => array(
/**
* Available option 'sandbox' or 'live'
* Remember sandbox id and secret will be different than live
*/
'mode' => 'sandbox',

/**
* Specify the max request time in seconds
*/
'http.ConnectionTimeOut' => 30,

/**
* Whether want to log to a file
*/
'log.LogEnabled' => true,

/**
* Specify the file that want to write on
*/
'log.FileName' => storage_path() . '/logs/paypal.log',

/**
* Available option 'FINE', 'INFO', 'WARN' or 'ERROR'
*
* Logging is most verbose in the 'FINE' level and decreases as you
* proceed towards ERROR
*/
'log.LogLevel' => 'FINE'
),
);
</pre>

2. Create Controller file

Now, Let's Create our controller in <b>app/Http/Controller/PaymentController.php</b>

<pre>

namespace App\Http\Controllers;

use Laracasts\Flash\Flash;
use Session;
use Illuminate\Http\Request;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\ExecutePayment;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Api\PaymentExecution;
use PayPal\Api\Transaction;

class PaymentController extends Controller
{

    private $_api_context;

    public function __construct()
    {
        // setup PayPal api context
        $paypal_conf = config('paypal');
        $this->_api_context = new ApiContext(new OAuthTokenCredential($paypal_conf['client_id'], $paypal_conf['secret']));
        $this->_api_context->setConfig($paypal_conf['settings']);

    }

    public function postPayment()
    {

        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        $item_1 = new Item();
        $item_1->setName('Item 1')// item name
        ->setCurrency('USD')
            ->setQuantity(2)
            ->setPrice('15'); // unit price

        // add item to list
        $item_list = new ItemList();
        $item_list->setItems(array($item_1));

        $amount = new Amount();
        $amount->setCurrency('USD')
            ->setTotal(30);

        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($item_list)
            ->setDescription('Your transaction description');

        $redirect_urls = new RedirectUrls();
        $redirect_urls->setReturnUrl(url('payment/status'))
            ->setCancelUrl(url('payment/status'));

        $payment = new Payment();
        $payment->setIntent('Sale')
            ->setPayer($payer)
            ->setRedirectUrls($redirect_urls)
            ->setTransactions(array($transaction));

        try {
            $payment->create($this->_api_context);
        } catch (PayPalConnectionException $ex) {
            if (\config('app.debug')) {
                echo "Exception: " . $ex->getMessage() . PHP_EOL;
                $err_data = json_decode($ex->getData(), true);
                exit;
            } else {
                Flash::error('Something went wrong, Sorry for inconvenience');
                return redirect('/');
            }
        }

        foreach ($payment->getLinks() as $link) {
            if ($link->getRel() == 'approval_url') {
                $redirect_url = $link->getHref();
                break;
            }
        }

        // add payment ID to session
        Session::put('paypal_payment_id', $payment->getId());

        if (isset($redirect_url)) {
            // redirect to paypal
            return redirect($redirect_url);
        }
        Flash::error('Unknown error occurred');
        return redirect('/');
    }

    public function getPaymentStatus(Request $request)
    {
        // Get the payment ID before session clear
        $payment_id = Session::get('paypal_payment_id');

        // clear the session payment ID
        Session::forget('paypal_payment_id');

        if (empty($request->input('PayerID')) || empty($request->input('token'))) {
            Flash::error('Payment Failed');
            return redirect('/');
        }

        $payment = Payment::get($payment_id, $this->_api_context);

        // PaymentExecution object includes information necessary
        // to execute a PayPal account payment.
        // The payer_id is added to the request query parameters
        // when the user is redirected from paypal back to your site
        $execution = new PaymentExecution();
        $execution->setPayerId($request->input('PayerID'));

        //Execute the payment
        $result = $payment->execute($execution, $this->_api_context);

        /*
        * Get the ID with : $result->id
        * Get the State with $result->state
        * Get the Payer State with $result->payer->payment_method
        * Get The Shipping Address and More Detail like below :- $result->payer->payer_info->shipping_address
        * Get More detail about shipping address like city country name
        */

        echo "<pre>";
        print_r($result);
        echo "</pre>";
        exit; // DEBUG RESULT.

        if ($result->getState() == 'approved') { // payment made
            Flash::success('Payment Successful');
            return redirect('home');
        }
        Flash::error('Payment Failed');
        return redirect('/');
    }
}
</pre>

Add Route

Now, add below route to our <b>routes/web.php</b> file

<pre>
// You can use "get" or "post" method below for payment..
Route::get('payment', 'PaymentController@postPayment');
// This must be get method.
Route::get('payment/status', 'PaymentController@getPaymentStatus');
</pre>

Let's Get Transaction details in same nature :-

Test Response

<pre>
$result->transactions[0]->amount->total
$result->transactions[0]->amount->currency

// Get The Item list
$result->transactions[0]->item_list->items[0]->price
$result->transactions[0]->item_list->items[0]->name
$result->transactions[0]->item_list->items[0]->quantity
$result->transactions[0]->item_list->items[0]->currency

// Similarly if you have added more than 1 item, you can change items[1] , items[2] and so on and so forth
</pre>

<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// You can use "get" or "post" method below for payment..
Route::get('payment', 'PaymentController@postPayment');
// This must be get method.
Route::get('payment/status', 'PaymentController@getPaymentStatus');
<?php
return array(
// set your paypal credential
// Below credentials are different for sandbox mode and live mode.
    'client_id' => 'AQh25ms4OxoZLQ_ntYYVL4nzPXh1EXEFczV0bqbJfCF661yaxjBHBtI6W86ODdbwIJ2qpZNHqRH3f9er',
    'secret' => 'EEZ4Dsxb23pOrK6dqH_8G1pCdLYcCKV3Rsnd8T3qnBDrHniv_XtEwjXqpHAeU2thizzkLQCB1F0SEFaW',

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
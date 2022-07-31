<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Messenger
    |--------------------------------------------------------------------------
    |
    | This option controls the default messenger that is used to send any text
    | messages sent by your application. Alternative messenger may be setup
    | and used as needed; however, this messenger will be used by default.
    |
    */

    'default' => env('SMS_MESSENGER', 'twilio'),

    /*
    |--------------------------------------------------------------------------
    | Messenger Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the messengers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | This package supports a variety of sms "transport" drivers to be used while
    | sending a text message. You will specify which one you are using for your
    | messengers below. You are free to add additional messengers as required.
    |
    | Supported: "twilio",
    |            "log", "array", "failover"
    |
    */

    'messengers' => [
        'twilio' => [
            'transport' => 'ses',
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('SMS_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'messengers' => [
                'twilio',
                'log',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Addresses
    |--------------------------------------------------------------------------
    |
    | You may wish for all text messages sent by your application to be sent from
    | or to the same address.
    |
    */

    'from' => env('SMS_FROM_NUMBER'),

    'to' => env('SMS_TO_NUMBER')

];

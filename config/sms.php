<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default SMS Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default provider that is used to send any sms
    | messages sent by the application. Alternative providers may be setup
    | and used as needed; however, this provider will be used by default.
    |
    */

    'default' => env('SMS_PROVIDER', 'email'),

    /*
    |--------------------------------------------------------------------------
    | SMS Provider Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the providers used by the application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Providers support a variety of sms "transport" drivers to be used while
    | sending a message. You will specify which one you are using for your
    | providers below. Feel free to add additional providers as needed.
    |
    | Supported: "array", "email", "log", "twilio"
    |
    */

    'providers' => [

        'array' => [
            'transport' => 'array',
        ],

        'email' => [
            'transport' => 'email',
            'gateways' => [
                // 'carrier' => 'domain'
            ]
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('SMS_LOG_CHANNEL'),
        ],

        'twilio' => [
            'transport' => 'twilio', // requires "reedware/laravel-sms-twilio"
            'account_sid' => env('TWILIO_SID'),
            'auth_token' => env('TWILIO_TOKEN')
        ]

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Number
    |--------------------------------------------------------------------------
    |
    | You may wish for all messages sent by your application to be sent from
    | the same number. Here, you may specify a phone number that is used
    | globally for all text messages that are sent by the application.
    |
    */

    'from' => env('SMS_FROM', '555-555-5555')

];

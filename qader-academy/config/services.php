<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | MyFatoorah Payment Gateway
    |--------------------------------------------------------------------------
    */

    'myfatoorah' => [
        'api_key' => env('MYFATOORAH_API_KEY'),
        'is_test' => env('MYFATOORAH_IS_TEST', true),
        'test_webhook_token' => env('MYFATOORAH_TEST_WEBHOOK_TOKEN'),
        'base_test_url' => env('MYFATOORAH_BASE_TEST_URL', 'https://apitest.myfatoorah.com'),
        'base_live_url' => env('MYFATOORAH_BASE_LIVE_URL', 'https://api.myfatoorah.com'),
    ],

];

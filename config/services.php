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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    ],

    'workos' => [
        'client_id' => env('WORKOS_CLIENT_ID'),
        'api_key' => env('WORKOS_API_KEY'),
        'app_redirect_url' => env('WORKOS_APP_REDIRECT_URL'),
    ],

    'smtp2go' => [
        'api_key' => env('SMTP2GO_API_KEY'),
        'endpoint' => env('SMTP2GO_API_ENDPOINT', 'https://api.smtp2go.com/v3/email/send'),
        'from_address' => env('SMTP2GO_FROM_ADDRESS', env('MAIL_FROM_ADDRESS')),
        'from_name' => env('SMTP2GO_FROM_NAME', env('MAIL_FROM_NAME')),
    ],

];

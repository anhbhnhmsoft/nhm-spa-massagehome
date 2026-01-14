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

    // Node server configuration
    'node_server' => [
        'host' => env('NODE_HOST', '0.0.0.0'),
        'port' => env('NODE_PORT', 3000),
        'channel_notification' => env('REDIS_CHANNEL_NOTIFICATION', 'expo_notifications'),
        'channel_chat' => env('REDIS_CHANNEL_CHAT', 'chat_messages'),
        'channel_chat_auth' => env('REDIS_CHANNEL_CHAT_AUTH', 'chat_auth'),
    ],
    'zalo' => [
        'app_id' => env('ZALO_APP_ID'),
        'app_secret' => env('ZALO_APP_SECRET'),
        'oa_id' => env('ZALO_OA_ID'),
        'otp_template' => env('ZALO_OTP_TEMPLATE'),
    ],
];

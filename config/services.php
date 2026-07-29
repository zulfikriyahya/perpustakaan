<?php

return [

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

    'whatsapp_gateway' => [
        'base_url' => env('WHATSAPP_GATEWAY_BASE_URL', 'https://whatsapp.zedlabs.id'),
        'api_key_id' => env('WHATSAPP_GATEWAY_API_KEY_ID'),
        'secret' => env('WHATSAPP_GATEWAY_SECRET'),
        'timeout' => env('WHATSAPP_GATEWAY_TIMEOUT', 15),
    ],

    'device_gateway' => [
        // Satu key statis untuk seluruh Attendance Machine (ESP32) - lihat
        // AuthenticateDeviceApiKey. Rotasi key wajib disertai reconfigure
        // seluruh device via provisioning mode (poin 17 Aturan).
        'api_key' => env('DEVICE_GATEWAY_API_KEY'),
    ],

];

<?php

return [
    'postmark' => ['key' => env('POSTMARK_API_KEY')],
    'resend' => ['key' => env('RESEND_API_KEY')],
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
    'paymob' => [
        'enabled' => (bool) env('PAYMOB_ENABLED', false),
        'base_url' => env('PAYMOB_BASE_URL', 'https://accept.paymob.com'),
        'secret_key' => env('PAYMOB_SECRET_KEY'),
        'public_key' => env('PAYMOB_PUBLIC_KEY'),
        'hmac_secret' => env('PAYMOB_HMAC_SECRET'),
        'integration_ids' => array_values(array_filter(array_map('intval', explode(',', (string) env('PAYMOB_INTEGRATION_IDS', ''))))),
        'notification_url' => env('PAYMOB_NOTIFICATION_URL'),
        'redirection_url' => env('PAYMOB_REDIRECTION_URL'),
        'timeout' => (int) env('PAYMOB_TIMEOUT', 15),
        'sandbox_probe_url' => env('PAYMOB_SANDBOX_PROBE_URL'),
    ],
    'kashier' => [
        'enabled' => (bool) env('KASHIER_ENABLED', false),
        'api_base_url' => env('KASHIER_API_BASE_URL', 'https://test-api.kashier.io'),
        'fep_base_url' => env('KASHIER_FEP_BASE_URL', 'https://test-fep.kashier.io'),
        'checkout_base_url' => env('KASHIER_CHECKOUT_BASE_URL', 'https://payments.kashier.io'),
        'merchant_id' => env('KASHIER_MERCHANT_ID'),
        'secret_key' => env('KASHIER_SECRET_KEY'),
        'payment_api_key' => env('KASHIER_PAYMENT_API_KEY'),
        'webhook_url' => env('KASHIER_WEBHOOK_URL'),
        'redirect_url' => env('KASHIER_REDIRECT_URL'),
        'timeout' => (int) env('KASHIER_TIMEOUT', 15),
        'sandbox_probe_url' => env('KASHIER_SANDBOX_PROBE_URL'),
    ],
    'bosta' => [
        'enabled' => (bool) env('BOSTA_ENABLED', false),
        'base_url' => env('BOSTA_BASE_URL', 'https://app.bosta.co'),
        'api_key' => env('BOSTA_API_KEY'),
        'webhook_url' => env('BOSTA_WEBHOOK_URL'),
        'webhook_auth_header' => env('BOSTA_WEBHOOK_AUTH_HEADER', 'Authorization'),
        'webhook_auth_value' => env('BOSTA_WEBHOOK_AUTH_VALUE'),
        'timeout' => (int) env('BOSTA_TIMEOUT', 15),
    ],
];

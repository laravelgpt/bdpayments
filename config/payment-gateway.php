<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment gateway that will be used
    | when no specific gateway is specified.
    |
    */

    'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'nagad'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the payment gateways for your application.
    | Each gateway has its own configuration options.
    |
    */

    'gateways' => [
        'nagad' => [
            'merchant_id' => env('NAGAD_MERCHANT_ID'),
            'merchant_private_key' => env('NAGAD_MERCHANT_PRIVATE_KEY'),
            'nagad_public_key' => env('NAGAD_PUBLIC_KEY'),
            'sandbox' => env('NAGAD_SANDBOX', true),
        ],
        
        'bkash' => [
            'app_key' => env('BKASH_APP_KEY'),
            'app_secret' => env('BKASH_APP_SECRET'),
            'username' => env('BKASH_USERNAME'),
            'password' => env('BKASH_PASSWORD'),
            'sandbox' => env('BKASH_SANDBOX', true),
        ],
        
        'binance' => [
            'api_key' => env('BINANCE_API_KEY'),
            'secret_key' => env('BINANCE_SECRET_KEY'),
            'sandbox' => env('BINANCE_SANDBOX', true),
        ],
        
        'paypal' => [
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'client_secret' => env('PAYPAL_CLIENT_SECRET'),
            'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox or live
            'brand_name' => env('PAYPAL_BRAND_NAME', 'BD Payments'),
        ],
        
        'rocket' => [
            'api_key' => env('ROCKET_API_KEY'),
            'secret_key' => env('ROCKET_SECRET_KEY'),
            'merchant_id' => env('ROCKET_MERCHANT_ID'),
            'sandbox' => env('ROCKET_SANDBOX', true),
        ],
        
        'upay' => [
            'api_key' => env('UPAY_API_KEY'),
            'secret_key' => env('UPAY_SECRET_KEY'),
            'merchant_id' => env('UPAY_MERCHANT_ID'),
            'sandbox' => env('UPAY_SANDBOX', true),
        ],
        
        'surecash' => [
            'api_key' => env('SURECASH_API_KEY'),
            'secret_key' => env('SURECASH_SECRET_KEY'),
            'merchant_id' => env('SURECASH_MERCHANT_ID'),
            'sandbox' => env('SURECASH_SANDBOX', true),
        ],
        
        'ucash' => [
            'api_key' => env('UCASH_API_KEY'),
            'secret_key' => env('UCASH_SECRET_KEY'),
            'merchant_id' => env('UCASH_MERCHANT_ID'),
            'sandbox' => env('UCASH_SANDBOX', true),
        ],
        
        'mcash' => [
            'api_key' => env('MCASH_API_KEY'),
            'secret_key' => env('MCASH_SECRET_KEY'),
            'merchant_id' => env('MCASH_MERCHANT_ID'),
            'sandbox' => env('MCASH_SANDBOX', true),
        ],
        
        'mycash' => [
            'api_key' => env('MYCASH_API_KEY'),
            'secret_key' => env('MYCASH_SECRET_KEY'),
            'merchant_id' => env('MYCASH_MERCHANT_ID'),
            'sandbox' => env('MYCASH_SANDBOX', true),
        ],
        
        'aamarpay' => [
            'store_id' => env('AAMARPAY_STORE_ID'),
            'signature_key' => env('AAMARPAY_SIGNATURE_KEY'),
            'api_key' => env('AAMARPAY_API_KEY'),
            'sandbox' => env('AAMARPAY_SANDBOX', true),
        ],
        
        'shurjopay' => [
            'merchant_id' => env('SHURJOPAY_MERCHANT_ID'),
            'merchant_password' => env('SHURJOPAY_MERCHANT_PASSWORD'),
            'api_key' => env('SHURJOPAY_API_KEY'),
            'sandbox' => env('SHURJOPAY_SANDBOX', true),
        ],
        
        'sslcommerz' => [
            'store_id' => env('SSLCOMMERZ_STORE_ID'),
            'store_password' => env('SSLCOMMERZ_STORE_PASSWORD'),
            'api_key' => env('SSLCOMMERZ_API_KEY'),
            'sandbox' => env('SSLCOMMERZ_SANDBOX', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Payment Settings
    |--------------------------------------------------------------------------
    |
    | These are the default settings for all payment operations.
    |
    */

    'defaults' => [
        'currency' => env('PAYMENT_DEFAULT_CURRENCY', 'BDT'),
        'timeout' => env('PAYMENT_TIMEOUT', 30),
        'retry_attempts' => env('PAYMENT_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('PAYMENT_RETRY_DELAY', 1000), // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the logging settings for payment operations.
    |
    */

    'logging' => [
        'enabled' => env('PAYMENT_LOGGING_ENABLED', true),
        'level' => env('PAYMENT_LOG_LEVEL', 'info'),
        'channel' => env('PAYMENT_LOG_CHANNEL', 'payment'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure rate limiting for payment operations.
    |
    */

    'rate_limits' => [
        'nagad' => [
            'max_attempts' => env('NAGAD_RATE_LIMIT_MAX_ATTEMPTS', 60),
            'decay_minutes' => env('NAGAD_RATE_LIMIT_DECAY_MINUTES', 1),
        ],
        'bkash' => [
            'max_attempts' => env('BKASH_RATE_LIMIT_MAX_ATTEMPTS', 60),
            'decay_minutes' => env('BKASH_RATE_LIMIT_DECAY_MINUTES', 1),
        ],
        'binance' => [
            'max_attempts' => env('BINANCE_RATE_LIMIT_MAX_ATTEMPTS', 60),
            'decay_minutes' => env('BINANCE_RATE_LIMIT_DECAY_MINUTES', 1),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure webhook settings for each payment gateway.
    |
    */

    'webhooks' => [
        'nagad' => [
            'enabled' => env('NAGAD_WEBHOOK_ENABLED', true),
            'secret' => env('NAGAD_WEBHOOK_SECRET'),
            'url' => env('NAGAD_WEBHOOK_URL'),
        ],
        'bkash' => [
            'enabled' => env('BKASH_WEBHOOK_ENABLED', true),
            'secret' => env('BKASH_WEBHOOK_SECRET'),
            'url' => env('BKASH_WEBHOOK_URL'),
        ],
        'binance' => [
            'enabled' => env('BINANCE_WEBHOOK_ENABLED', true),
            'secret' => env('BINANCE_WEBHOOK_SECRET'),
            'url' => env('BINANCE_WEBHOOK_URL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure security settings for payment operations.
    |
    */

    'security' => [
        'encrypt_sensitive_data' => env('PAYMENT_ENCRYPT_SENSITIVE_DATA', true),
        'sanitize_logs' => env('PAYMENT_SANITIZE_LOGS', true),
        'require_https' => env('PAYMENT_REQUIRE_HTTPS', true),
        'webhook_secret' => env('PAYMENT_WEBHOOK_SECRET'),
        'hash_secret' => env('PAYMENT_HASH_SECRET', env('APP_KEY')),
        'transaction_prefix' => env('PAYMENT_TRANSACTION_PREFIX', 'TXN'),
        'reference_prefix' => env('PAYMENT_REFERENCE_PREFIX', 'REF'),
        'max_amount' => env('PAYMENT_MAX_AMOUNT', 10000),
        'min_amount' => env('PAYMENT_MIN_AMOUNT', 0.01),
        'rate_limit' => [
            'enabled' => env('PAYMENT_RATE_LIMIT_ENABLED', true),
            'max_attempts' => env('PAYMENT_RATE_LIMIT_MAX_ATTEMPTS', 5),
            'window_minutes' => env('PAYMENT_RATE_LIMIT_WINDOW_MINUTES', 15),
        ],
        'fraud_detection' => [
            'enabled' => env('PAYMENT_FRAUD_DETECTION_ENABLED', true),
            'suspicious_ip_check' => env('PAYMENT_SUSPICIOUS_IP_CHECK', true),
            'rapid_payment_check' => env('PAYMENT_RAPID_PAYMENT_CHECK', true),
            'unusual_amount_check' => env('PAYMENT_UNUSUAL_AMOUNT_CHECK', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure cache settings for payment operations.
    |
    */

    'cache' => [
        'enabled' => env('PAYMENT_CACHE_ENABLED', true),
        'driver' => env('PAYMENT_CACHE_DRIVER', 'redis'),
        'prefix' => env('PAYMENT_CACHE_PREFIX', 'payment_gateway'),
        'ttl' => env('PAYMENT_CACHE_TTL', 3600), // seconds
    ],
];

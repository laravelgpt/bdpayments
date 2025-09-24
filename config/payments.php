<?php

declare(strict_types=1);

/**
 * Payment Gateway Configuration
 * 
 * This file contains the configuration for Nagad and bKash payment gateways.
 * Copy this file to your project and update the values according to your setup.
 */

return [
    'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'nagad'),
    
    'gateways' => [
        'nagad' => [
            'gateway' => 'nagad',
            'merchant_id' => env('NAGAD_MERCHANT_ID'),
            'merchant_private_key' => env('NAGAD_MERCHANT_PRIVATE_KEY'),
            'nagad_public_key' => env('NAGAD_PUBLIC_KEY'),
            'sandbox' => env('NAGAD_SANDBOX', true),
        ],
        
        'bkash' => [
            'gateway' => 'bkash',
            'app_key' => env('BKASH_APP_KEY'),
            'app_secret' => env('BKASH_APP_SECRET'),
            'username' => env('BKASH_USERNAME'),
            'password' => env('BKASH_PASSWORD'),
            'sandbox' => env('BKASH_SANDBOX', true),
        ],
    ],
    
    'defaults' => [
        'currency' => 'BDT',
        'timeout' => 30,
        'retry_attempts' => 3,
        'retry_delay' => 1000, // milliseconds
    ],
    
    'logging' => [
        'enabled' => env('PAYMENT_LOGGING_ENABLED', true),
        'level' => env('PAYMENT_LOG_LEVEL', 'info'),
        'channel' => env('PAYMENT_LOG_CHANNEL', 'payment'),
    ],
    
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
    ],
];

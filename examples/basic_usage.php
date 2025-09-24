<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use BDPayments\NagadBkashGateway\PaymentManager;
use BDPayments\NagadBkashGateway\PaymentFactory;
use BDPayments\NagadBkashGateway\Exceptions\PaymentException;

/**
 * Basic usage example for Nagad and bKash payment gateways
 */

// Example 1: Using PaymentManager with multiple gateways
echo "=== Example 1: PaymentManager ===\n";

$configurations = [
    'nagad' => [
        'gateway' => 'nagad',
        'merchant_id' => 'your_merchant_id',
        'merchant_private_key' => 'your_merchant_private_key',
        'nagad_public_key' => 'your_nagad_public_key',
        'sandbox' => true,
    ],
    'bkash' => [
        'gateway' => 'bkash',
        'app_key' => 'your_app_key',
        'app_secret' => 'your_app_secret',
        'username' => 'your_username',
        'password' => 'your_password',
        'sandbox' => true,
    ],
];

try {
    $paymentManager = new PaymentManager($configurations);

    // Initialize payment with Nagad
    $paymentData = [
        'order_id' => 'ORDER_' . time(),
        'amount' => 100.50,
        'currency' => 'BDT',
        'callback_url' => 'https://yourdomain.com/callback',
    ];

    echo "Initializing payment with Nagad...\n";
    $response = $paymentManager->initializePayment('nagad', $paymentData);

    if ($response->success) {
        echo "✅ Payment initialized successfully!\n";
        echo "Payment ID: {$response->paymentId}\n";
        echo "Redirect URL: {$response->redirectUrl}\n";
        echo "Amount: {$response->amount} {$response->currency}\n";
    } else {
        echo "❌ Payment failed: {$response->message}\n";
    }

    // Initialize payment with bKash
    echo "\nInitializing payment with bKash...\n";
    $bkashResponse = $paymentManager->initializePayment('bkash', $paymentData);

    if ($bkashResponse->success) {
        echo "✅ Payment initialized successfully!\n";
        echo "Payment ID: {$bkashResponse->paymentId}\n";
        echo "Redirect URL: {$bkashResponse->redirectUrl}\n";
        echo "Amount: {$bkashResponse->amount} {$bkashResponse->currency}\n";
    } else {
        echo "❌ Payment failed: {$bkashResponse->message}\n";
    }

} catch (PaymentException $e) {
    echo "❌ Payment error: {$e->getMessage()}\n";
}

// Example 2: Using individual gateways
echo "\n\n=== Example 2: Individual Gateways ===\n";

try {
    // Create Nagad gateway directly
    $nagadConfig = [
        'merchant_id' => 'your_merchant_id',
        'merchant_private_key' => 'your_merchant_private_key',
        'nagad_public_key' => 'your_nagad_public_key',
        'sandbox' => true,
    ];

    $nagadGateway = PaymentFactory::create('nagad', $nagadConfig);

    echo "Nagad Gateway Name: {$nagadGateway->getGatewayName()}\n";
    echo "Nagad Configured: " . ($nagadGateway->isConfigured() ? 'Yes' : 'No') . "\n";

    // Create bKash gateway directly
    $bkashConfig = [
        'app_key' => 'your_app_key',
        'app_secret' => 'your_app_secret',
        'username' => 'your_username',
        'password' => 'your_password',
        'sandbox' => true,
    ];

    $bkashGateway = PaymentFactory::create('bkash', $bkashConfig);

    echo "bKash Gateway Name: {$bkashGateway->getGatewayName()}\n";
    echo "bKash Configured: " . ($bkashGateway->isConfigured() ? 'Yes' : 'No') . "\n";

} catch (PaymentException $e) {
    echo "❌ Gateway creation error: {$e->getMessage()}\n";
}

// Example 3: Error handling
echo "\n\n=== Example 3: Error Handling ===\n";

try {
    // This will fail due to missing configuration
    $invalidConfig = [
        'gateway' => 'nagad',
        // Missing required fields
    ];

    $paymentManager = new PaymentManager(['nagad' => $invalidConfig]);
    $response = $paymentManager->initializePayment('nagad', [
        'order_id' => 'ORDER123',
        'amount' => 100,
    ]);

} catch (\BDPayments\NagadBkashGateway\Exceptions\ConfigurationException $e) {
    echo "❌ Configuration Error: {$e->getMessage()}\n";
} catch (\BDPayments\NagadBkashGateway\Exceptions\ValidationException $e) {
    echo "❌ Validation Error: {$e->getMessage()}\n";
} catch (\BDPayments\NagadBkashGateway\Exceptions\NetworkException $e) {
    echo "❌ Network Error: {$e->getMessage()}\n";
} catch (PaymentException $e) {
    echo "❌ Payment Error: {$e->getMessage()}\n";
}

echo "\n=== Examples completed ===\n";

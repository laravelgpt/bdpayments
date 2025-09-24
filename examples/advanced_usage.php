<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use BDPayments\NagadBkashGateway\PaymentManager;
use BDPayments\NagadBkashGateway\Config\Config;
use BDPayments\NagadBkashGateway\Logger\PaymentLogger;
use BDPayments\NagadBkashGateway\Validators\PaymentValidator;
use BDPayments\NagadBkashGateway\Exceptions\PaymentException;

/**
 * Advanced usage example with configuration, logging, and validation
 */

echo "=== Advanced Payment Gateway Usage ===\n";

// Example 1: Configuration Management
echo "\n1. Configuration Management:\n";

$config = new Config();
$config->loadFromFile(__DIR__ . '/../config/payments.php');

echo "Default Gateway: " . $config->get('default_gateway', 'not set') . "\n";
echo "Nagad Sandbox: " . ($config->get('gateways.nagad.sandbox') ? 'Yes' : 'No') . "\n";
echo "bKash Sandbox: " . ($config->get('gateways.bkash.sandbox') ? 'Yes' : 'No') . "\n";

// Example 2: Payment Validation
echo "\n2. Payment Data Validation:\n";

$validPaymentData = [
    'order_id' => 'ORDER_' . time(),
    'amount' => 100.50,
    'currency' => 'BDT',
    'callback_url' => 'https://example.com/callback',
];

try {
    PaymentValidator::validatePaymentData($validPaymentData);
    echo "✅ Payment data is valid\n";
} catch (\BDPayments\NagadBkashGateway\Exceptions\ValidationException $e) {
    echo "❌ Validation failed: {$e->getMessage()}\n";
}

// Test invalid data
$invalidPaymentData = [
    'order_id' => '', // Empty order ID
    'amount' => -100, // Negative amount
    'currency' => 'EUR', // Invalid currency
];

try {
    PaymentValidator::validatePaymentData($invalidPaymentData);
    echo "❌ Validation should have failed\n";
} catch (\BDPayments\NagadBkashGateway\Exceptions\ValidationException $e) {
    echo "✅ Validation correctly failed: {$e->getMessage()}\n";
}

// Example 3: Data Sanitization
echo "\n3. Data Sanitization:\n";

$rawPaymentData = [
    'order_id' => '  ORDER123  ', // Extra spaces
    'amount' => '100.50', // String amount
    'currency' => 'bdt', // Lowercase currency
    'callback_url' => 'https://example.com/callback',
];

$sanitizedData = PaymentValidator::sanitizePaymentData($rawPaymentData);

echo "Original Order ID: '{$rawPaymentData['order_id']}'\n";
echo "Sanitized Order ID: '{$sanitizedData['order_id']}'\n";
echo "Original Currency: '{$rawPaymentData['currency']}'\n";
echo "Sanitized Currency: '{$sanitizedData['currency']}'\n";
echo "Original Amount: '{$rawPaymentData['amount']}' (type: " . gettype($rawPaymentData['amount']) . ")\n";
echo "Sanitized Amount: {$sanitizedData['amount']} (type: " . gettype($sanitizedData['amount']) . ")\n";

// Example 4: Payment Logging
echo "\n4. Payment Logging:\n";

$logger = new PaymentLogger(true);

// Simulate payment operations
$paymentData = [
    'order_id' => 'ORDER_' . time(),
    'amount' => 100.50,
    'currency' => 'BDT',
];

// Mock successful response
$mockResponse = \BDPayments\NagadBkashGateway\PaymentResponse::success(
    'Payment initialized successfully',
    ['gateway' => 'nagad'],
    'PAYMENT123',
    'https://nagad.com/pay/PAYMENT123',
    'PAYMENT123',
    100.50,
    'BDT',
    'pending'
);

$logger->logPaymentInitialization('nagad', $paymentData, $mockResponse);

// Mock verification response
$verificationResponse = \BDPayments\NagadBkashGateway\PaymentResponse::success(
    'Payment verified successfully',
    ['gateway' => 'nagad'],
    'PAYMENT123',
    null,
    'PAYMENT123',
    100.50,
    'BDT',
    'completed'
);

$logger->logPaymentVerification('nagad', 'PAYMENT123', $verificationResponse);

// Mock refund response
$refundResponse = \BDPayments\NagadBkashGateway\PaymentResponse::success(
    'Payment refunded successfully',
    ['gateway' => 'nagad'],
    'REFUND123',
    null,
    'REFUND123',
    50.0,
    'BDT',
    'refunded'
);

$logger->logPaymentRefund('nagad', 'PAYMENT123', 50.0, 'Customer request', $refundResponse);

echo "Total logs: " . count($logger->getLogs()) . "\n";
echo "Nagad logs: " . count($logger->getLogsByGateway('nagad')) . "\n";
echo "Payment logs: " . count($logger->getLogsByPaymentId('PAYMENT123')) . "\n";

// Example 5: Response Handling
echo "\n5. Response Handling:\n";

// Success response
$successResponse = \BDPayments\NagadBkashGateway\PaymentResponse::success(
    'Payment successful',
    ['transaction_id' => 'TXN123'],
    'PAYMENT123',
    'https://example.com/success',
    'TXN123',
    100.50,
    'BDT',
    'completed'
);

echo "Success Response:\n";
echo "Success: " . ($successResponse->success ? 'Yes' : 'No') . "\n";
echo "Message: {$successResponse->message}\n";
echo "Payment ID: {$successResponse->paymentId}\n";
echo "Amount: {$successResponse->amount} {$successResponse->currency}\n";
echo "Status: {$successResponse->status}\n";

// Failure response
$failureResponse = \BDPayments\NagadBkashGateway\PaymentResponse::failure(
    'Payment failed',
    ['error_code' => 'INSUFFICIENT_FUNDS'],
    400
);

echo "\nFailure Response:\n";
echo "Success: " . ($failureResponse->success ? 'Yes' : 'No') . "\n";
echo "Message: {$failureResponse->message}\n";
echo "HTTP Code: {$failureResponse->httpCode}\n";

// JSON output
echo "\nJSON Response:\n";
echo $successResponse->toJson() . "\n";

// Example 6: Gateway Factory
echo "\n6. Gateway Factory:\n";

echo "Supported Gateways:\n";
$supportedGateways = \BDPayments\NagadBkashGateway\PaymentFactory::getSupportedGateways();
foreach ($supportedGateways as $name => $description) {
    echo "- {$name}: {$description}\n";
}

echo "\nGateway Support Check:\n";
echo "Nagad supported: " . (\BDPayments\NagadBkashGateway\PaymentFactory::isGatewaySupported('nagad') ? 'Yes' : 'No') . "\n";
echo "bKash supported: " . (\BDPayments\NagadBkashGateway\PaymentFactory::isGatewaySupported('bkash') ? 'Yes' : 'No') . "\n";
echo "Invalid supported: " . (\BDPayments\NagadBkashGateway\PaymentFactory::isGatewaySupported('invalid') ? 'Yes' : 'No') . "\n";

echo "\n=== Advanced Examples Completed ===\n";

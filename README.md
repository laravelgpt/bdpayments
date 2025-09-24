# Laravel Payment Gateway Package

A comprehensive Laravel 12 package for integrating Nagad, bKash, and Binance payment gateways with PHP 8.4+ support, featuring advanced payment management, invoice generation, and problem resolution.

## Features

- 🚀 **Laravel 12 & PHP 8.4+ Support** - Built with modern Laravel and PHP features
- 💳 **Multiple Gateways** - Support for Nagad, bKash, and Binance payment gateways
- 🔒 **Secure** - Built-in validation, rate limiting, and webhook signature verification
- 📝 **Comprehensive Logging** - Track all payment operations with detailed logs
- 🧪 **Well Tested** - Full test coverage with PHPUnit and Laravel testing
- 🛠️ **Easy Integration** - Simple facade and service provider integration
- 📊 **Rich Models** - Eloquent models for payments, logs, refunds, and invoices
- 🎨 **Beautiful Views** - Responsive payment forms and result pages
- ⚡ **Performance** - Caching, rate limiting, and optimized database queries
- 📋 **Payment History** - Complete payment lifecycle tracking
- 🧾 **Invoice Generation** - Automatic invoice creation and management
- 🔄 **Refund Management** - Comprehensive refund tracking and history
- 🚨 **Problem Resolution** - Advanced problem reporting and resolution system
- 📊 **Admin Dashboard** - Complete admin interface for payment management
- 📈 **Analytics** - Payment statistics and reporting
- 🎯 **Status Tracking** - Real-time payment status monitoring

## Installation

```bash
composer require bd-payments/laravel-payment-gateway
```

### Publish Configuration

```bash
php artisan vendor:publish --provider="BDPayments\LaravelPaymentGateway\Providers\PaymentGatewayServiceProvider" --tag="config"
```

### Publish Migrations

```bash
php artisan vendor:publish --provider="BDPayments\LaravelPaymentGateway\Providers\PaymentGatewayServiceProvider" --tag="migrations"
```

### Run Migrations

```bash
php artisan migrate
```

### Publish Views (Optional)

```bash
php artisan vendor:publish --provider="BDPayments\LaravelPaymentGateway\Providers\PaymentGatewayServiceProvider" --tag="views"
```

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
# Default Gateway
PAYMENT_DEFAULT_GATEWAY=nagad

# Nagad Configuration
NAGAD_MERCHANT_ID=your_merchant_id_here
NAGAD_MERCHANT_PRIVATE_KEY=your_merchant_private_key_here
NAGAD_PUBLIC_KEY=your_nagad_public_key_here
NAGAD_SANDBOX=true

# bKash Configuration
BKASH_APP_KEY=your_app_key_here
BKASH_APP_SECRET=your_app_secret_here
BKASH_USERNAME=your_username_here
BKASH_PASSWORD=your_password_here
BKASH_SANDBOX=true

# Binance Configuration
BINANCE_API_KEY=your_api_key_here
BINANCE_SECRET_KEY=your_secret_key_here
BINANCE_SANDBOX=true

# Logging Configuration
PAYMENT_LOGGING_ENABLED=true
PAYMENT_LOG_LEVEL=info
```

### Configuration File

The package will create `config/payment-gateway.php` with comprehensive configuration options.

## Quick Start

### Basic Usage

```php
<?php

use BDPayments\LaravelPaymentGateway\Facades\PaymentGateway;

// Initialize a payment
$response = PaymentGateway::initializePayment('nagad', [
    'order_id' => 'ORDER123',
    'amount' => 100.50,
    'currency' => 'BDT',
    'callback_url' => 'https://yourdomain.com/callback',
]);

if ($response->success) {
    // Redirect to payment gateway
    return redirect($response->redirectUrl);
} else {
    // Handle error
    return back()->with('error', $response->message);
}
```

### Using Service Injection

```php
<?php

use BDPayments\LaravelPaymentGateway\Services\PaymentGatewayService;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayService $paymentService
    ) {}

    public function processPayment(Request $request)
    {
        $response = $this->paymentService->initializePayment('nagad', [
            'order_id' => $request->order_id,
            'amount' => $request->amount,
            'currency' => 'BDT',
        ]);

        return response()->json($response->toArray());
    }
}
```

### Using Models

```php
<?php

use BDPayments\LaravelPaymentGateway\Models\Payment;

// Create a payment record
$payment = Payment::create([
    'user_id' => auth()->id(),
    'order_id' => 'ORDER123',
    'gateway' => 'nagad',
    'amount' => 100.50,
    'currency' => 'BDT',
    'status' => 'pending',
]);

// Check payment status
if ($payment->isPending()) {
    // Payment is still pending
}

// Mark as completed
$payment->markAsCompleted($gatewayResponse);

// Check if refundable
if ($payment->canBeRefunded()) {
    // Process refund
}
```

## Advanced Features

### Payment History Tracking

```php
use BDPayments\LaravelPaymentGateway\Services\PaymentHistoryService;

$historyService = app(PaymentHistoryService::class);

// Log payment actions
$historyService->logPaymentCreated($payment);
$historyService->logPaymentCompleted($payment, $gatewayResponse);
$historyService->logPaymentFailed($payment, $gatewayResponse, 'Insufficient funds');

// Report payment problems
$problem = $historyService->reportProblem(
    $payment,
    'payment_failed',
    'Payment Processing Error',
    'Customer reported payment failure',
    'high',
    'urgent'
);

// Get payment history
$history = $historyService->getPaymentHistory($payment);
```

### Invoice Generation

```php
use BDPayments\LaravelPaymentGateway\Services\InvoiceService;

$invoiceService = app(InvoiceService::class);

// Generate invoice for payment
$invoice = $invoiceService->generateInvoice($payment, [
    'billing_address' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'address' => '123 Main St',
        'city' => 'Dhaka',
        'country' => 'Bangladesh',
    ],
    'items' => [
        [
            'description' => 'Product A',
            'quantity' => 2,
            'unit_price' => 50.00,
            'tax_rate' => 10,
        ],
    ],
]);

// Send invoice
$invoiceService->sendInvoice($invoice);

// Generate PDF
$pdf = $invoiceService->generateInvoicePdf($invoice);
```

### Problem Resolution System

```php
use BDPayments\LaravelPaymentGateway\Models\PaymentProblem;

// Get payment problems
$problems = PaymentProblem::open()->critical()->get();

// Assign problem to admin
$problem->assignTo($adminId);

// Resolve problem
$problem->markAsResolved($adminId, 'Issue resolved by contacting gateway support');

// Add comments to problems
$problem->comments()->create([
    'user_id' => auth()->id(),
    'comment' => 'Working on this issue',
    'is_internal' => true,
]);
```

### Admin Dashboard

```php
// Access admin routes
Route::prefix('admin/payment')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [PaymentAdminController::class, 'dashboard']);
    Route::get('/payments', [PaymentAdminController::class, 'index']);
    Route::get('/problems', [PaymentAdminController::class, 'problems']);
    Route::get('/invoices', [PaymentAdminController::class, 'invoices']);
});
```

## API Reference

### PaymentGateway Facade

```php
use BDPayments\LaravelPaymentGateway\Facades\PaymentGateway;

// Initialize payment
$response = PaymentGateway::initializePayment(string $gateway, array $data);

// Verify payment
$response = PaymentGateway::verifyPayment(string $gateway, string $paymentId);

// Refund payment
$response = PaymentGateway::refundPayment(string $gateway, string $paymentId, float $amount, string $reason = '');

// Get payment status
$response = PaymentGateway::getPaymentStatus(string $gateway, string $paymentId);

// Get supported gateways
$gateways = PaymentGateway::getSupportedGateways();

// Check if gateway is supported
$supported = PaymentGateway::isGatewaySupported(string $gateway);
```

### Payment Model

```php
use BDPayments\LaravelPaymentGateway\Models\Payment;

// Create payment
$payment = Payment::create($data);

// Status checks
$payment->isPending();
$payment->isCompleted();
$payment->isFailed();
$payment->isRefunded();
$payment->isExpired();

// Status updates
$payment->markAsCompleted($gatewayResponse);
$payment->markAsFailed($gatewayResponse);
$payment->markAsCancelled();

// Refund operations
$payment->canBeRefunded();
$payment->getTotalRefundedAmount();
$payment->getRemainingRefundableAmount();
$payment->updateRefundedAmount();
```

### Routes

The package automatically registers the following routes:

```php
// Payment form
GET /payment/form

// Initialize payment
POST /payment/initialize

// Gateway-specific operations
POST /payment/{gateway}/verify
POST /payment/{gateway}/refund
GET /payment/{gateway}/status
GET /payment/{gateway}/callback
POST /payment/{gateway}/webhook

// Result pages
GET /payment/success
GET /payment/failed

// API routes (with api middleware)
POST /api/payment/initialize
POST /api/payment/{gateway}/verify
POST /api/payment/{gateway}/refund
GET /api/payment/{gateway}/status
POST /api/payment/{gateway}/webhook
```

## Middleware

The package includes several middleware for security and rate limiting:

### PaymentGatewayMiddleware

Logs all payment operations and validates gateway parameters.

### WebhookSignatureMiddleware

Verifies webhook signatures for secure webhook processing.

### RateLimitMiddleware

Implements rate limiting for payment operations.

```php
// Apply middleware to routes
Route::middleware(['payment.gateway', 'payment.rate_limit:60,1'])->group(function () {
    // Payment routes
});
```

## Views

The package includes beautiful, responsive views:

- **Payment Form** (`payment-gateway::payment-form`) - Payment initialization form
- **Success Page** (`payment-gateway::success`) - Payment success page
- **Failed Page** (`payment-gateway::failed`) - Payment failure page

### Customizing Views

```bash
php artisan vendor:publish --provider="BDPayments\LaravelPaymentGateway\Providers\PaymentGatewayServiceProvider" --tag="views"
```

Then modify the views in `resources/views/vendor/payment-gateway/`.

## Logging

The package provides comprehensive logging:

```php
use BDPayments\LaravelPaymentGateway\Services\PaymentLogger;

$logger = app(PaymentLogger::class);

// Get all logs
$logs = $logger->getLogs();

// Get logs by gateway
$nagadLogs = $logger->getLogsByGateway('nagad');

// Get logs by payment ID
$paymentLogs = $logger->getLogsByPaymentId('PAYMENT123');

// Export logs
$logger->exportToFile('payment_logs.json');
```

## Testing

```bash
# Run tests
composer test

# Run tests with coverage
composer test-coverage

# Run static analysis
composer stan

# Run code style checks
composer cs

# Fix code style issues
composer cs-fix
```

## Error Handling

The package provides specific exception types:

```php
use BDPayments\LaravelPaymentGateway\Exceptions\PaymentException;
use BDPayments\LaravelPaymentGateway\Exceptions\ValidationException;
use BDPayments\LaravelPaymentGateway\Exceptions\ConfigurationException;
use BDPayments\LaravelPaymentGateway\Exceptions\NetworkException;

try {
    $response = PaymentGateway::initializePayment('nagad', $data);
} catch (ValidationException $e) {
    // Handle validation errors
} catch (ConfigurationException $e) {
    // Handle configuration errors
} catch (NetworkException $e) {
    // Handle network errors
} catch (PaymentException $e) {
    // Handle other payment errors
}
```

## Webhooks

Configure webhook endpoints in your `.env`:

```env
NAGAD_WEBHOOK_ENABLED=true
NAGAD_WEBHOOK_SECRET=your_webhook_secret
NAGAD_WEBHOOK_URL=https://yourdomain.com/payment/nagad/webhook

BKASH_WEBHOOK_ENABLED=true
BKASH_WEBHOOK_SECRET=your_webhook_secret
BKASH_WEBHOOK_URL=https://yourdomain.com/payment/bkash/webhook

BINANCE_WEBHOOK_ENABLED=true
BINANCE_WEBHOOK_SECRET=your_webhook_secret
BINANCE_WEBHOOK_URL=https://yourdomain.com/payment/binance/webhook
```

## Database Schema

The package includes migrations for:

- `payments` - Payment records
- `payment_logs` - Payment operation logs
- `payment_refunds` - Refund records

## Security Features

- **Rate Limiting** - Prevent abuse with configurable rate limits
- **Webhook Signature Verification** - Secure webhook processing
- **Data Sanitization** - Automatic sanitization of sensitive data
- **HTTPS Enforcement** - Optional HTTPS requirement
- **Input Validation** - Comprehensive validation rules

## Performance Features

- **Caching** - Configurable caching for improved performance
- **Database Optimization** - Efficient queries with proper indexing
- **Logging Control** - Optional logging to reduce overhead
- **Async Processing** - Support for background job processing

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass
6. Submit a pull request

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Support

For support and questions, please open an issue on GitHub or contact us at support@bdpayments.com.

## Database Schema

The package includes comprehensive database migrations:

- `payments` - Payment records with full lifecycle tracking
- `payment_logs` - Detailed operation logs
- `payment_refunds` - Refund management
- `payment_histories` - Complete payment history
- `payment_problems` - Problem reporting and resolution
- `payment_problem_comments` - Problem discussion threads
- `invoices` - Invoice generation and management
- `invoice_items` - Invoice line items

## Admin Features

### Payment Management
- View all payments with filtering and search
- Update payment status manually
- View detailed payment information
- Export payment data to CSV
- Real-time payment statistics

### Problem Resolution
- Report and track payment problems
- Assign problems to admin users
- Add comments and attachments
- Set priority and severity levels
- Resolve problems with detailed notes

### Invoice Management
- Generate invoices automatically
- Send invoices via email
- Download invoice PDFs
- Track invoice status
- Manage invoice items and calculations

### Analytics Dashboard
- Payment statistics and trends
- Problem resolution metrics
- Invoice generation reports
- Gateway performance analysis
- Revenue tracking

## Changelog

### v3.0.0
- **Payment History Tracking** - Complete payment lifecycle monitoring
- **Invoice Generation** - Automatic invoice creation and management
- **Problem Resolution System** - Advanced problem reporting and resolution
- **Admin Dashboard** - Comprehensive admin interface
- **Refund Management** - Enhanced refund tracking and history
- **Analytics & Reporting** - Payment statistics and insights
- **Status Monitoring** - Real-time payment status tracking
- **Enhanced Security** - Advanced problem resolution workflows

### v2.0.0
- Laravel 12 support
- PHP 8.4+ support
- Added Binance payment gateway
- Complete Laravel package structure
- Comprehensive testing
- Beautiful responsive views
- Advanced security features
- Performance optimizations

### v1.0.0
- Initial release
- Support for Nagad and bKash payment gateways
- Basic Laravel integration
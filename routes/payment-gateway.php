<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use BDPayments\LaravelPaymentGateway\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| Payment Gateway Routes
|--------------------------------------------------------------------------
|
| Here are the routes for the payment gateway package. These routes handle
| payment initialization, verification, refunds, callbacks, and webhooks.
|
*/

Route::prefix('payment')->name('payment.')->group(function () {
    
    // Public routes
    Route::get('/form', [PaymentController::class, 'showPaymentForm'])->name('form');
    Route::post('/initialize', [PaymentController::class, 'initializePayment'])->name('initialize');
    
    // Gateway-specific routes
    Route::prefix('{gateway}')->where('gateway', 'nagad|bkash|binance')->group(function () {
        Route::post('/verify', [PaymentController::class, 'verifyPayment'])->name('verify');
        Route::post('/refund', [PaymentController::class, 'refundPayment'])->name('refund');
        Route::get('/status', [PaymentController::class, 'getPaymentStatus'])->name('status');
        Route::get('/callback', [PaymentController::class, 'handleCallback'])->name('callback');
        Route::post('/webhook', [PaymentController::class, 'handleWebhook'])->name('webhook');
    });
    
    // Result pages
    Route::get('/success', [PaymentController::class, 'showSuccess'])->name('success');
    Route::get('/failed', [PaymentController::class, 'showFailed'])->name('failed');
});

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| API routes for programmatic access to payment gateway functionality.
| These routes are protected by the 'api' middleware group.
|
*/

Route::prefix('api/payment')->name('api.payment.')->middleware(['api'])->group(function () {
    
    Route::post('/initialize', [PaymentController::class, 'initializePayment'])->name('initialize');
    
    // Gateway-specific API routes
    Route::prefix('{gateway}')->where('gateway', 'nagad|bkash|binance')->group(function () {
        Route::post('/verify', [PaymentController::class, 'verifyPayment'])->name('verify');
        Route::post('/refund', [PaymentController::class, 'refundPayment'])->name('refund');
        Route::get('/status', [PaymentController::class, 'getPaymentStatus'])->name('status');
        Route::post('/webhook', [PaymentController::class, 'handleWebhook'])->name('webhook');
    });
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Admin routes for managing payments, viewing logs, and handling refunds.
| These routes are protected by the 'admin' middleware group.
|
*/

Route::prefix('admin/payment')->name('admin.payment.')->middleware(['web', 'auth', 'admin'])->group(function () {
    
    // Payment management
    Route::get('/', [PaymentController::class, 'index'])->name('index');
    Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
    Route::post('/{payment}/refund', [PaymentController::class, 'refundPayment'])->name('refund');
    
    // Logs and reports
    Route::get('/logs', [PaymentController::class, 'logs'])->name('logs');
    Route::get('/reports', [PaymentController::class, 'reports'])->name('reports');
    Route::get('/export', [PaymentController::class, 'export'])->name('export');
});

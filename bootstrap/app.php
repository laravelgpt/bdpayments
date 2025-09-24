<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register payment gateway middleware
        $middleware->alias([
            'payment.gateway' => \BDPayments\LaravelPaymentGateway\Http\Middleware\PaymentGatewayMiddleware::class,
            'payment.webhook' => \BDPayments\LaravelPaymentGateway\Http\Middleware\WebhookSignatureMiddleware::class,
            'payment.rate_limit' => \BDPayments\LaravelPaymentGateway\Http\Middleware\RateLimitMiddleware::class,
        ]);

        // Apply middleware to payment routes
        $middleware->group('payment', [
            'payment.gateway',
            'payment.rate_limit:60,1',
        ]);

        $middleware->group('webhook', [
            'payment.webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle payment gateway exceptions
        $exceptions->render(function (\BDPayments\LaravelPaymentGateway\Exceptions\PaymentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ], 400);
        });

        $exceptions->render(function (\BDPayments\LaravelPaymentGateway\Exceptions\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'VALIDATION_ERROR',
            ], 422);
        });

        $exceptions->render(function (\BDPayments\LaravelPaymentGateway\Exceptions\ConfigurationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'CONFIGURATION_ERROR',
            ], 500);
        });

        $exceptions->render(function (\BDPayments\LaravelPaymentGateway\Exceptions\NetworkException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'NETWORK_ERROR',
            ], 503);
        });
    });

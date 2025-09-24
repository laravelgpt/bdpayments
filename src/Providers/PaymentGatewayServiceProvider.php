<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Providers;

use Illuminate\Support\ServiceProvider;
use BDPayments\LaravelPaymentGateway\Services\PaymentGatewayService;
use BDPayments\LaravelPaymentGateway\Services\PaymentManager;
use BDPayments\LaravelPaymentGateway\Services\PaymentLogger;
use BDPayments\LaravelPaymentGateway\Services\PaymentValidator;
use BDPayments\LaravelPaymentGateway\Contracts\PaymentGatewayInterface;
use BDPayments\LaravelPaymentGateway\Gateways\NagadGateway;
use BDPayments\LaravelPaymentGateway\Gateways\BkashGateway;
use BDPayments\LaravelPaymentGateway\Gateways\BinanceGateway;

class PaymentGatewayServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/payment-gateway.php',
            'payment-gateway'
        );

        $this->app->singleton(PaymentManager::class, function ($app) {
            return new PaymentManager(config('payment-gateway.gateways', []));
        });

        $this->app->singleton(PaymentGatewayService::class, function ($app) {
            return new PaymentGatewayService($app->make(PaymentManager::class));
        });

        $this->app->singleton(PaymentLogger::class, function ($app) {
            return new PaymentLogger(config('payment-gateway.logging.enabled', true));
        });

        $this->app->singleton(PaymentValidator::class, function ($app) {
            return new PaymentValidator();
        });

        // Register gateways
        $this->app->bind('payment.gateway.nagad', function ($app) {
            $config = config('payment-gateway.gateways.nagad', []);
            return new NagadGateway(
                $config['merchant_id'] ?? '',
                $config['merchant_private_key'] ?? '',
                $config['nagad_public_key'] ?? '',
                $config['sandbox'] ?? true
            );
        });

        $this->app->bind('payment.gateway.bkash', function ($app) {
            $config = config('payment-gateway.gateways.bkash', []);
            return new BkashGateway(
                $config['app_key'] ?? '',
                $config['app_secret'] ?? '',
                $config['username'] ?? '',
                $config['password'] ?? '',
                $config['sandbox'] ?? true
            );
        });

        $this->app->bind('payment.gateway.binance', function ($app) {
            $config = config('payment-gateway.gateways.binance', []);
            return new BinanceGateway(
                $config['api_key'] ?? '',
                $config['secret_key'] ?? '',
                $config['sandbox'] ?? true
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/payment-gateway.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'payment-gateway');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/payment-gateway.php' => config_path('payment-gateway.php'),
            ], 'config');

            $this->publishes([
                __DIR__ . '/../../database/migrations' => database_path('migrations'),
            ], 'migrations');

            $this->publishes([
                __DIR__ . '/../../resources/views' => resource_path('views/vendor/payment-gateway'),
            ], 'views');

            $this->publishes([
                __DIR__ . '/../../public' => public_path('vendor/payment-gateway'),
            ], 'assets');
        }
    }
}

<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Factories;

use BDPayments\LaravelPaymentGateway\Contracts\GatewayFactoryInterface;
use BDPayments\LaravelPaymentGateway\Contracts\PaymentGatewayInterface;
use BDPayments\LaravelPaymentGateway\Exceptions\ConfigurationException;
use BDPayments\LaravelPaymentGateway\Gateways\NagadGateway;
use BDPayments\LaravelPaymentGateway\Gateways\BkashGateway;
use BDPayments\LaravelPaymentGateway\Gateways\BinanceGateway;
use BDPayments\LaravelPaymentGateway\Gateways\PayPalGateway;
use BDPayments\LaravelPaymentGateway\Gateways\RocketGateway;
use BDPayments\LaravelPaymentGateway\Gateways\UpayGateway;
use BDPayments\LaravelPaymentGateway\Gateways\SureCashGateway;
use BDPayments\LaravelPaymentGateway\Gateways\UCashGateway;
use BDPayments\LaravelPaymentGateway\Gateways\MCashGateway;
use BDPayments\LaravelPaymentGateway\Gateways\MyCashGateway;
use BDPayments\LaravelPaymentGateway\Gateways\AamarPayGateway;
use BDPayments\LaravelPaymentGateway\Gateways\ShurjoPayGateway;
use BDPayments\LaravelPaymentGateway\Gateways\SSLCOMMERZGateway;
use Illuminate\Support\Facades\Log;

class GatewayFactory implements GatewayFactoryInterface
{
    private array $gateways = [];
    private array $customGateways = [];

    public function __construct()
    {
        $this->registerDefaultGateways();
    }

    /**
     * Create a payment gateway instance.
     */
    public function create(string $gateway, array $config): PaymentGatewayInterface
    {
        $gateway = strtolower($gateway);

        if (!$this->isGatewayAvailable($gateway)) {
            throw new ConfigurationException("Gateway '{$gateway}' is not available");
        }

        $gatewayClass = $this->getGatewayClass($gateway);

        if (!class_exists($gatewayClass)) {
            throw new ConfigurationException("Gateway class '{$gatewayClass}' does not exist");
        }

        try {
            return new $gatewayClass($config);
        } catch (\Exception $e) {
            Log::error("Failed to create gateway '{$gateway}'", [
                'gateway' => $gateway,
                'config' => $this->sanitizeConfig($config),
                'error' => $e->getMessage(),
            ]);

            throw new ConfigurationException("Failed to create gateway '{$gateway}': " . $e->getMessage());
        }
    }

    /**
     * Get all available gateways.
     */
    public function getAvailableGateways(): array
    {
        return array_merge($this->gateways, $this->customGateways);
    }

    /**
     * Check if gateway is available.
     */
    public function isGatewayAvailable(string $gateway): bool
    {
        $gateway = strtolower($gateway);
        return isset($this->gateways[$gateway]) || isset($this->customGateways[$gateway]);
    }

    /**
     * Register a custom gateway.
     */
    public function registerGateway(string $name, string $class): void
    {
        $name = strtolower($name);

        if (!class_exists($class)) {
            throw new ConfigurationException("Gateway class '{$class}' does not exist");
        }

        if (!is_subclass_of($class, PaymentGatewayInterface::class)) {
            throw new ConfigurationException("Gateway class '{$class}' must implement PaymentGatewayInterface");
        }

        $this->customGateways[$name] = $class;

        Log::info("Custom gateway registered", [
            'name' => $name,
            'class' => $class,
        ]);
    }

    /**
     * Get gateway configuration.
     */
    public function getGatewayConfig(string $gateway): array
    {
        $gateway = strtolower($gateway);
        $config = config("payment-gateway.gateways.{$gateway}", []);

        if (empty($config)) {
            throw new ConfigurationException("Configuration for gateway '{$gateway}' not found");
        }

        return $config;
    }

    /**
     * Register default gateways.
     */
    private function registerDefaultGateways(): void
    {
        $this->gateways = [
            'nagad' => NagadGateway::class,
            'bkash' => BkashGateway::class,
            'binance' => BinanceGateway::class,
            'paypal' => PayPalGateway::class,
            'rocket' => RocketGateway::class,
            'upay' => UpayGateway::class,
            'surecash' => SureCashGateway::class,
            'ucash' => UCashGateway::class,
            'mcash' => MCashGateway::class,
            'mycash' => MyCashGateway::class,
            'aamarpay' => AamarPayGateway::class,
            'shurjopay' => ShurjoPayGateway::class,
            'sslcommerz' => SSLCOMMERZGateway::class,
        ];
    }

    /**
     * Get gateway class.
     */
    private function getGatewayClass(string $gateway): string
    {
        if (isset($this->customGateways[$gateway])) {
            return $this->customGateways[$gateway];
        }

        if (isset($this->gateways[$gateway])) {
            return $this->gateways[$gateway];
        }

        throw new ConfigurationException("Gateway '{$gateway}' not found");
    }

    /**
     * Sanitize configuration for logging.
     */
    private function sanitizeConfig(array $config): array
    {
        $sensitiveKeys = [
            'api_key', 'secret_key', 'password', 'private_key', 'signature_key',
            'client_secret', 'merchant_password', 'store_password'
        ];

        $sanitized = $config;

        foreach ($sensitiveKeys as $key) {
            if (isset($sanitized[$key])) {
                $sanitized[$key] = '[REDACTED]';
            }
        }

        return $sanitized;
    }
}

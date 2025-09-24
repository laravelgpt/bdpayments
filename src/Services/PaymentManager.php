<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Services;

use BDPayments\LaravelPaymentGateway\Contracts\PaymentGatewayInterface;
use BDPayments\LaravelPaymentGateway\PaymentResponse;
use BDPayments\LaravelPaymentGateway\Exceptions\PaymentException;
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

class PaymentManager
{
    private array $gateways = [];

    public function __construct(array $configurations = [])
    {
        foreach ($configurations as $name => $config) {
            $this->addGateway($name, $config);
        }
    }

    /**
     * Add a payment gateway
     */
    public function addGateway(string $name, array $config): self
    {
        if (!isset($config['gateway'])) {
            throw new ConfigurationException("Gateway type not specified for '{$name}'");
        }

        $this->gateways[$name] = $this->createGateway($config['gateway'], $config);
        
        return $this;
    }

    /**
     * Get a payment gateway by name
     */
    public function getGateway(string $name): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$name])) {
            throw new ConfigurationException("Gateway '{$name}' not found");
        }

        return $this->gateways[$name];
    }

    /**
     * Initialize payment with specified gateway
     */
    public function initializePayment(string $gatewayName, array $paymentData): PaymentResponse
    {
        $gateway = $this->getGateway($gatewayName);
        return $gateway->initializePayment($paymentData);
    }

    /**
     * Verify payment with specified gateway
     */
    public function verifyPayment(string $gatewayName, string $paymentId): PaymentResponse
    {
        $gateway = $this->getGateway($gatewayName);
        return $gateway->verifyPayment($paymentId);
    }

    /**
     * Refund payment with specified gateway
     */
    public function refundPayment(string $gatewayName, string $paymentId, float $amount, string $reason = ''): PaymentResponse
    {
        $gateway = $this->getGateway($gatewayName);
        return $gateway->refundPayment($paymentId, $amount, $reason);
    }

    /**
     * Get payment status with specified gateway
     */
    public function getPaymentStatus(string $gatewayName, string $paymentId): PaymentResponse
    {
        $gateway = $this->getGateway($gatewayName);
        return $gateway->getPaymentStatus($paymentId);
    }

    /**
     * Get all configured gateways
     */
    public function getGateways(): array
    {
        return $this->gateways;
    }

    /**
     * Get supported gateways
     */
    public function getSupportedGateways(): array
    {
        return [
            'nagad' => 'Nagad Payment Gateway',
            'bkash' => 'bKash Payment Gateway',
            'binance' => 'Binance Payment Gateway',
            'paypal' => 'PayPal Payment Gateway',
            'rocket' => 'Rocket Payment Gateway',
            'upay' => 'Upay Payment Gateway',
            'surecash' => 'SureCash Payment Gateway',
            'ucash' => 'UCash Payment Gateway',
            'mcash' => 'MCash Payment Gateway',
            'mycash' => 'MyCash Payment Gateway',
            'aamarpay' => 'AamarPay Payment Gateway',
            'shurjopay' => 'ShurjoPay Payment Gateway',
            'sslcommerz' => 'SSLCOMMERZ Payment Gateway',
        ];
    }

    /**
     * Check if gateway is supported
     */
    public function isGatewaySupported(string $gateway): bool
    {
        return in_array(strtolower($gateway), [
            'nagad', 'bkash', 'binance', 'paypal', 'rocket', 'upay', 'surecash',
            'ucash', 'mcash', 'mycash', 'aamarpay', 'shurjopay', 'sslcommerz'
        ], true);
    }

    /**
     * Get gateway names
     */
    public function getGatewayNames(): array
    {
        return array_keys($this->gateways);
    }

    /**
     * Check if gateway exists
     */
    public function hasGateway(string $name): bool
    {
        return isset($this->gateways[$name]);
    }

    /**
     * Remove a gateway
     */
    public function removeGateway(string $name): self
    {
        unset($this->gateways[$name]);
        return $this;
    }

    /**
     * Create a gateway instance
     */
    private function createGateway(string $gateway, array $config): PaymentGatewayInterface
    {
        return match (strtolower($gateway)) {
            'nagad' => $this->createNagadGateway($config),
            'bkash' => $this->createBkashGateway($config),
            'binance' => $this->createBinanceGateway($config),
            'paypal' => $this->createPayPalGateway($config),
            'rocket' => $this->createRocketGateway($config),
            'upay' => $this->createUpayGateway($config),
            'surecash' => $this->createSureCashGateway($config),
            'ucash' => $this->createUCashGateway($config),
            'mcash' => $this->createMCashGateway($config),
            'mycash' => $this->createMyCashGateway($config),
            'aamarpay' => $this->createAamarPayGateway($config),
            'shurjopay' => $this->createShurjoPayGateway($config),
            'sslcommerz' => $this->createSSLCOMMERZGateway($config),
            default => throw new ConfigurationException("Unsupported gateway: {$gateway}"),
        };
    }

    /**
     * Create Nagad gateway instance
     */
    private function createNagadGateway(array $config): NagadGateway
    {
        $requiredFields = ['merchant_id', 'merchant_private_key', 'nagad_public_key'];
        
        foreach ($requiredFields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                throw new ConfigurationException("Missing required Nagad configuration: {$field}");
            }
        }

        return new NagadGateway(
            merchantId: $config['merchant_id'],
            merchantPrivateKey: $config['merchant_private_key'],
            nagadPublicKey: $config['nagad_public_key'],
            sandbox: $config['sandbox'] ?? true
        );
    }

    /**
     * Create bKash gateway instance
     */
    private function createBkashGateway(array $config): BkashGateway
    {
        $requiredFields = ['app_key', 'app_secret', 'username', 'password'];
        
        foreach ($requiredFields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                throw new ConfigurationException("Missing required bKash configuration: {$field}");
            }
        }

        return new BkashGateway(
            appKey: $config['app_key'],
            appSecret: $config['app_secret'],
            username: $config['username'],
            password: $config['password'],
            sandbox: $config['sandbox'] ?? true
        );
    }

    /**
     * Create Binance gateway instance
     */
    private function createBinanceGateway(array $config): BinanceGateway
    {
        $requiredFields = ['api_key', 'secret_key'];
        
        foreach ($requiredFields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                throw new ConfigurationException("Missing required Binance configuration: {$field}");
            }
        }

        return new BinanceGateway(
            apiKey: $config['api_key'],
            secretKey: $config['secret_key'],
            sandbox: $config['sandbox'] ?? true
        );
    }

    /**
     * Create PayPal gateway instance
     */
    private function createPayPalGateway(array $config): PayPalGateway
    {
        $requiredFields = ['client_id', 'client_secret', 'mode'];
        
        foreach ($requiredFields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                throw new ConfigurationException("Missing required PayPal configuration: {$field}");
            }
        }

        return new PayPalGateway($config);
    }

    /**
     * Create Rocket gateway instance
     */
    private function createRocketGateway(array $config): RocketGateway
    {
        $requiredFields = ['api_key', 'secret_key', 'merchant_id'];
        
        foreach ($requiredFields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                throw new ConfigurationException("Missing required Rocket configuration: {$field}");
            }
        }

        return new RocketGateway($config);
    }

    /**
     * Create Upay gateway instance
     */
    private function createUpayGateway(array $config): UpayGateway
    {
        $requiredFields = ['api_key', 'secret_key', 'merchant_id'];
        
        foreach ($requiredFields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                throw new ConfigurationException("Missing required Upay configuration: {$field}");
            }
        }

        return new UpayGateway($config);
    }

    /**
     * Create SureCash gateway instance
     */
    private function createSureCashGateway(array $config): SureCashGateway
    {
        $requiredFields = ['api_key', 'secret_key', 'merchant_id'];
        
        foreach ($requiredFields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                throw new ConfigurationException("Missing required SureCash configuration: {$field}");
            }
        }

        return new SureCashGateway($config);
    }

    /**
     * Create UCash gateway instance
     */
    private function createUCashGateway(array $config): UCashGateway
    {
        $requiredFields = ['api_key', 'secret_key', 'merchant_id'];
        
        foreach ($requiredFields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                throw new ConfigurationException("Missing required UCash configuration: {$field}");
            }
        }

        return new UCashGateway($config);
    }

    /**
     * Create MCash gateway instance
     */
    private function createMCashGateway(array $config): MCashGateway
    {
        $requiredFields = ['api_key', 'secret_key', 'merchant_id'];
        
        foreach ($requiredFields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                throw new ConfigurationException("Missing required MCash configuration: {$field}");
            }
        }

        return new MCashGateway($config);
    }

    /**
     * Create MyCash gateway instance
     */
    private function createMyCashGateway(array $config): MyCashGateway
    {
        $requiredFields = ['api_key', 'secret_key', 'merchant_id'];
        
        foreach ($requiredFields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                throw new ConfigurationException("Missing required MyCash configuration: {$field}");
            }
        }

        return new MyCashGateway($config);
    }

    /**
     * Create AamarPay gateway instance
     */
    private function createAamarPayGateway(array $config): AamarPayGateway
    {
        $requiredFields = ['store_id', 'signature_key', 'api_key'];
        
        foreach ($requiredFields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                throw new ConfigurationException("Missing required AamarPay configuration: {$field}");
            }
        }

        return new AamarPayGateway($config);
    }

    /**
     * Create ShurjoPay gateway instance
     */
    private function createShurjoPayGateway(array $config): ShurjoPayGateway
    {
        $requiredFields = ['merchant_id', 'merchant_password', 'api_key'];
        
        foreach ($requiredFields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                throw new ConfigurationException("Missing required ShurjoPay configuration: {$field}");
            }
        }

        return new ShurjoPayGateway($config);
    }

    /**
     * Create SSLCOMMERZ gateway instance
     */
    private function createSSLCOMMERZGateway(array $config): SSLCOMMERZGateway
    {
        $requiredFields = ['store_id', 'store_password', 'api_key'];
        
        foreach ($requiredFields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                throw new ConfigurationException("Missing required SSLCOMMERZ configuration: {$field}");
            }
        }

        return new SSLCOMMERZGateway($config);
    }
}

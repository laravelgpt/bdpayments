<?php

declare(strict_types=1);

namespace BDPayments\NagadBkashGateway;

use BDPayments\NagadBkashGateway\Gateways\NagadGateway;
use BDPayments\NagadBkashGateway\Gateways\BkashGateway;
use BDPayments\NagadBkashGateway\Exceptions\ConfigurationException;

/**
 * Factory class for creating payment gateway instances
 */
class PaymentFactory
{
    public const GATEWAY_NAGAD = 'nagad';
    public const GATEWAY_BKASH = 'bkash';

    /**
     * Create a payment gateway instance
     *
     * @param string $gateway Gateway name (nagad, bkash)
     * @param array $config Gateway configuration
     * @return PaymentGatewayInterface
     * @throws ConfigurationException
     */
    public static function create(string $gateway, array $config): PaymentGatewayInterface
    {
        return match (strtolower($gateway)) {
            self::GATEWAY_NAGAD => self::createNagadGateway($config),
            self::GATEWAY_BKASH => self::createBkashGateway($config),
            default => throw new ConfigurationException("Unsupported gateway: {$gateway}"),
        };
    }

    /**
     * Create Nagad gateway instance
     */
    private static function createNagadGateway(array $config): NagadGateway
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
            sandbox: $config['sandbox'] ?? true,
            httpClient: $config['http_client'] ?? null
        );
    }

    /**
     * Create bKash gateway instance
     */
    private static function createBkashGateway(array $config): BkashGateway
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
            sandbox: $config['sandbox'] ?? true,
            httpClient: $config['http_client'] ?? null
        );
    }

    /**
     * Get list of supported gateways
     *
     * @return array
     */
    public static function getSupportedGateways(): array
    {
        return [
            self::GATEWAY_NAGAD => 'Nagad Payment Gateway',
            self::GATEWAY_BKASH => 'bKash Payment Gateway',
        ];
    }

    /**
     * Check if a gateway is supported
     *
     * @param string $gateway
     * @return bool
     */
    public static function isGatewaySupported(string $gateway): bool
    {
        return in_array(strtolower($gateway), [self::GATEWAY_NAGAD, self::GATEWAY_BKASH], true);
    }
}

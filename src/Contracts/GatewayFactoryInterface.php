<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Contracts;

use BDPayments\LaravelPaymentGateway\Contracts\PaymentGatewayInterface;

interface GatewayFactoryInterface
{
    /**
     * Create a payment gateway instance.
     */
    public function create(string $gateway, array $config): PaymentGatewayInterface;

    /**
     * Get all available gateways.
     */
    public function getAvailableGateways(): array;

    /**
     * Check if gateway is available.
     */
    public function isGatewayAvailable(string $gateway): bool;

    /**
     * Register a custom gateway.
     */
    public function registerGateway(string $name, string $class): void;

    /**
     * Get gateway configuration.
     */
    public function getGatewayConfig(string $gateway): array;
}

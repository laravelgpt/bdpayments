<?php

declare(strict_types=1);

namespace BDPayments\NagadBkashGateway;

use BDPayments\NagadBkashGateway\Exceptions\ConfigurationException;
use BDPayments\NagadBkashGateway\Exceptions\PaymentException;

/**
 * Main payment manager class for handling multiple gateways
 */
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
     *
     * @param string $name Gateway name
     * @param array $config Gateway configuration
     * @return self
     * @throws ConfigurationException
     */
    public function addGateway(string $name, array $config): self
    {
        if (!isset($config['gateway'])) {
            throw new ConfigurationException("Gateway type not specified for '{$name}'");
        }

        $this->gateways[$name] = PaymentFactory::create($config['gateway'], $config);
        
        return $this;
    }

    /**
     * Get a payment gateway by name
     *
     * @param string $name Gateway name
     * @return PaymentGatewayInterface
     * @throws ConfigurationException
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
     *
     * @param string $gatewayName Gateway name
     * @param array $paymentData Payment data
     * @return PaymentResponse
     * @throws PaymentException
     */
    public function initializePayment(string $gatewayName, array $paymentData): PaymentResponse
    {
        $gateway = $this->getGateway($gatewayName);
        return $gateway->initializePayment($paymentData);
    }

    /**
     * Verify payment with specified gateway
     *
     * @param string $gatewayName Gateway name
     * @param string $paymentId Payment ID
     * @return PaymentResponse
     * @throws PaymentException
     */
    public function verifyPayment(string $gatewayName, string $paymentId): PaymentResponse
    {
        $gateway = $this->getGateway($gatewayName);
        return $gateway->verifyPayment($paymentId);
    }

    /**
     * Refund payment with specified gateway
     *
     * @param string $gatewayName Gateway name
     * @param string $paymentId Payment ID
     * @param float $amount Refund amount
     * @param string $reason Refund reason
     * @return PaymentResponse
     * @throws PaymentException
     */
    public function refundPayment(string $gatewayName, string $paymentId, float $amount, string $reason = ''): PaymentResponse
    {
        $gateway = $this->getGateway($gatewayName);
        return $gateway->refundPayment($paymentId, $amount, $reason);
    }

    /**
     * Get payment status with specified gateway
     *
     * @param string $gatewayName Gateway name
     * @param string $paymentId Payment ID
     * @return PaymentResponse
     * @throws PaymentException
     */
    public function getPaymentStatus(string $gatewayName, string $paymentId): PaymentResponse
    {
        $gateway = $this->getGateway($gatewayName);
        return $gateway->getPaymentStatus($paymentId);
    }

    /**
     * Get all configured gateways
     *
     * @return array
     */
    public function getGateways(): array
    {
        return $this->gateways;
    }

    /**
     * Get gateway names
     *
     * @return array
     */
    public function getGatewayNames(): array
    {
        return array_keys($this->gateways);
    }

    /**
     * Check if gateway exists
     *
     * @param string $name Gateway name
     * @return bool
     */
    public function hasGateway(string $name): bool
    {
        return isset($this->gateways[$name]);
    }

    /**
     * Remove a gateway
     *
     * @param string $name Gateway name
     * @return self
     */
    public function removeGateway(string $name): self
    {
        unset($this->gateways[$name]);
        return $this;
    }
}

<?php

declare(strict_types=1);

namespace BDPayments\NagadBkashGateway;

use BDPayments\NagadBkashGateway\Exceptions\PaymentException;

/**
 * Interface for payment gateway implementations
 */
interface PaymentGatewayInterface
{
    /**
     * Initialize a payment transaction
     *
     * @param array $paymentData Payment data including amount, order_id, etc.
     * @return PaymentResponse
     * @throws PaymentException
     */
    public function initializePayment(array $paymentData): PaymentResponse;

    /**
     * Verify a payment transaction
     *
     * @param string $paymentId Payment ID to verify
     * @return PaymentResponse
     * @throws PaymentException
     */
    public function verifyPayment(string $paymentId): PaymentResponse;

    /**
     * Refund a payment transaction
     *
     * @param string $paymentId Payment ID to refund
     * @param float $amount Refund amount
     * @param string $reason Refund reason
     * @return PaymentResponse
     * @throws PaymentException
     */
    public function refundPayment(string $paymentId, float $amount, string $reason = ''): PaymentResponse;

    /**
     * Get payment status
     *
     * @param string $paymentId Payment ID to check
     * @return PaymentResponse
     * @throws PaymentException
     */
    public function getPaymentStatus(string $paymentId): PaymentResponse;

    /**
     * Get gateway name
     *
     * @return string
     */
    public function getGatewayName(): string;

    /**
     * Check if gateway is configured properly
     *
     * @return bool
     */
    public function isConfigured(): bool;
}

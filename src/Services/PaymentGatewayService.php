<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Services;

use BDPayments\LaravelPaymentGateway\Services\PaymentManager;
use BDPayments\LaravelPaymentGateway\Services\PaymentLogger;
use BDPayments\LaravelPaymentGateway\Services\PaymentValidator;
use BDPayments\LaravelPaymentGateway\PaymentResponse;
use BDPayments\LaravelPaymentGateway\Exceptions\PaymentException;
use Illuminate\Support\Facades\Log;

class PaymentGatewayService
{
    public function __construct(
        private readonly PaymentManager $paymentManager,
        private readonly PaymentLogger $logger,
        private readonly PaymentValidator $validator
    ) {}

    /**
     * Initialize a payment
     */
    public function initializePayment(string $gateway, array $paymentData): PaymentResponse
    {
        try {
            // Validate payment data
            $this->validator->validatePaymentData($paymentData);
            
            // Sanitize payment data
            $sanitizedData = $this->validator->sanitizePaymentData($paymentData);
            
            // Initialize payment
            $response = $this->paymentManager->initializePayment($gateway, $sanitizedData);
            
            // Log payment initialization
            $this->logger->logPaymentInitialization($gateway, $sanitizedData, $response);
            
            return $response;
            
        } catch (PaymentException $e) {
            Log::error('Payment initialization failed', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
                'data' => $paymentData
            ]);
            
            throw $e;
        }
    }

    /**
     * Verify a payment
     */
    public function verifyPayment(string $gateway, string $paymentId): PaymentResponse
    {
        try {
            // Validate payment ID
            $this->validator->validatePaymentId($paymentId);
            
            // Verify payment
            $response = $this->paymentManager->verifyPayment($gateway, $paymentId);
            
            // Log payment verification
            $this->logger->logPaymentVerification($gateway, $paymentId, $response);
            
            return $response;
            
        } catch (PaymentException $e) {
            Log::error('Payment verification failed', [
                'gateway' => $gateway,
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Refund a payment
     */
    public function refundPayment(string $gateway, string $paymentId, float $amount, string $reason = ''): PaymentResponse
    {
        try {
            // Validate refund data
            $this->validator->validateRefundData($paymentId, $amount, $reason);
            
            // Refund payment
            $response = $this->paymentManager->refundPayment($gateway, $paymentId, $amount, $reason);
            
            // Log payment refund
            $this->logger->logPaymentRefund($gateway, $paymentId, $amount, $reason, $response);
            
            return $response;
            
        } catch (PaymentException $e) {
            Log::error('Payment refund failed', [
                'gateway' => $gateway,
                'payment_id' => $paymentId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(string $gateway, string $paymentId): PaymentResponse
    {
        try {
            // Validate payment ID
            $this->validator->validatePaymentId($paymentId);
            
            // Get payment status
            $response = $this->paymentManager->getPaymentStatus($gateway, $paymentId);
            
            // Log payment status check
            $this->logger->logPaymentStatus($gateway, $paymentId, $response);
            
            return $response;
            
        } catch (PaymentException $e) {
            Log::error('Payment status check failed', [
                'gateway' => $gateway,
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Get supported gateways
     */
    public function getSupportedGateways(): array
    {
        return $this->paymentManager->getSupportedGateways();
    }

    /**
     * Check if gateway is supported
     */
    public function isGatewaySupported(string $gateway): bool
    {
        return $this->paymentManager->isGatewaySupported($gateway);
    }

    /**
     * Get gateway configuration
     */
    public function getGatewayConfig(string $gateway): array
    {
        return config("payment-gateway.gateways.{$gateway}", []);
    }

    /**
     * Log payment operation
     */
    public function logPayment(string $operation, string $gateway, array $data, ?PaymentResponse $response = null): void
    {
        $this->logger->log($operation, $gateway, $data, $response);
    }
}

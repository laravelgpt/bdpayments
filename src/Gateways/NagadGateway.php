<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Gateways;

use BDPayments\LaravelPaymentGateway\Contracts\PaymentGatewayInterface;
use BDPayments\LaravelPaymentGateway\PaymentResponse;
use BDPayments\LaravelPaymentGateway\Exceptions\PaymentException;
use BDPayments\LaravelPaymentGateway\Exceptions\ConfigurationException;
use BDPayments\LaravelPaymentGateway\Exceptions\NetworkException;
use BDPayments\LaravelPaymentGateway\Exceptions\ValidationException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Nagad Payment Gateway Implementation
 */
class NagadGateway implements PaymentGatewayInterface
{
    private const GATEWAY_NAME = 'nagad';
    private const SANDBOX_BASE_URL = 'https://api-sandbox.mynagad.com:3070';
    private const PRODUCTION_BASE_URL = 'https://api.mynagad.com:3070';

    public function __construct(
        private readonly string $merchantId,
        private readonly string $merchantPrivateKey,
        private readonly string $nagadPublicKey,
        private readonly bool $sandbox = true,
        private readonly ?Client $httpClient = null
    ) {
        $this->validateConfiguration();
    }

    /**
     * Initialize a payment transaction with Nagad
     */
    public function initializePayment(array $paymentData): PaymentResponse
    {
        try {
            $this->validatePaymentData($paymentData);

            $client = $this->getHttpClient();
            $baseUrl = $this->getBaseUrl();

            // Step 1: Initialize payment
            $initializeData = [
                'merchantId' => $this->merchantId,
                'orderId' => $paymentData['order_id'],
                'amount' => $paymentData['amount'],
                'currencyCode' => $paymentData['currency'] ?? 'BDT',
                'datetime' => date('YmdHis'),
                'challenge' => $this->generateChallenge(),
            ];

            $response = $client->post("{$baseUrl}/api/initialize", [
                'json' => $initializeData,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if (!$responseData || !isset($responseData['status'])) {
                throw new PaymentException('Invalid response from Nagad API');
            }

            if ($responseData['status'] !== 'Success') {
                return PaymentResponse::failure(
                    $responseData['message'] ?? 'Payment initialization failed',
                    $responseData
                );
            }

            // Step 2: Complete payment
            $completeData = [
                'merchantId' => $this->merchantId,
                'orderId' => $paymentData['order_id'],
                'paymentRefId' => $responseData['paymentRefId'],
                'amount' => $paymentData['amount'],
                'challenge' => $this->generateChallenge(),
            ];

            $completeResponse = $client->post("{$baseUrl}/api/complete", [
                'json' => $completeData,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);

            $completeData = json_decode($completeResponse->getBody()->getContents(), true);

            if (!$completeData || !isset($completeData['status'])) {
                throw new PaymentException('Invalid response from Nagad API');
            }

            if ($completeData['status'] !== 'Success') {
                return PaymentResponse::failure(
                    $completeData['message'] ?? 'Payment completion failed',
                    $completeData
                );
            }

            return PaymentResponse::success(
                'Payment initialized successfully',
                $completeData,
                $completeData['paymentRefId'] ?? null,
                $completeData['callBackUrl'] ?? null,
                $completeData['paymentRefId'] ?? null,
                (float) $paymentData['amount'],
                $paymentData['currency'] ?? 'BDT',
                'pending'
            );

        } catch (GuzzleException $e) {
            throw new NetworkException('Network error during payment initialization: ' . $e->getMessage());
        } catch (PaymentException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new PaymentException('Unexpected error during payment initialization: ' . $e->getMessage());
        }
    }

    /**
     * Verify a payment transaction
     */
    public function verifyPayment(string $paymentId): PaymentResponse
    {
        try {
            $client = $this->getHttpClient();
            $baseUrl = $this->getBaseUrl();

            $response = $client->get("{$baseUrl}/api/verify/{$paymentId}", [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if (!$responseData) {
                throw new PaymentException('Invalid response from Nagad API');
            }

            if ($responseData['status'] !== 'Success') {
                return PaymentResponse::failure(
                    $responseData['message'] ?? 'Payment verification failed',
                    $responseData
                );
            }

            return PaymentResponse::success(
                'Payment verified successfully',
                $responseData,
                $responseData['paymentRefId'] ?? null,
                null,
                $responseData['paymentRefId'] ?? null,
                isset($responseData['amount']) ? (float) $responseData['amount'] : null,
                $responseData['currencyCode'] ?? 'BDT',
                $responseData['status'] ?? 'verified'
            );

        } catch (GuzzleException $e) {
            throw new NetworkException('Network error during payment verification: ' . $e->getMessage());
        } catch (PaymentException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new PaymentException('Unexpected error during payment verification: ' . $e->getMessage());
        }
    }

    /**
     * Refund a payment transaction
     */
    public function refundPayment(string $paymentId, float $amount, string $reason = ''): PaymentResponse
    {
        try {
            $client = $this->getHttpClient();
            $baseUrl = $this->getBaseUrl();

            $refundData = [
                'merchantId' => $this->merchantId,
                'paymentRefId' => $paymentId,
                'amount' => $amount,
                'reason' => $reason,
                'datetime' => date('YmdHis'),
            ];

            $response = $client->post("{$baseUrl}/api/refund", [
                'json' => $refundData,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if (!$responseData) {
                throw new PaymentException('Invalid response from Nagad API');
            }

            if ($responseData['status'] !== 'Success') {
                return PaymentResponse::failure(
                    $responseData['message'] ?? 'Payment refund failed',
                    $responseData
                );
            }

            return PaymentResponse::success(
                'Payment refunded successfully',
                $responseData,
                $responseData['refundId'] ?? null,
                null,
                $responseData['refundId'] ?? null,
                $amount,
                'BDT',
                'refunded'
            );

        } catch (GuzzleException $e) {
            throw new NetworkException('Network error during payment refund: ' . $e->getMessage());
        } catch (PaymentException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new PaymentException('Unexpected error during payment refund: ' . $e->getMessage());
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(string $paymentId): PaymentResponse
    {
        return $this->verifyPayment($paymentId);
    }

    /**
     * Get gateway name
     */
    public function getGatewayName(): string
    {
        return self::GATEWAY_NAME;
    }

    /**
     * Check if gateway is configured properly
     */
    public function isConfigured(): bool
    {
        return !empty($this->merchantId) && 
               !empty($this->merchantPrivateKey) && 
               !empty($this->nagadPublicKey);
    }

    /**
     * Validate configuration
     */
    private function validateConfiguration(): void
    {
        if (empty($this->merchantId)) {
            throw new ConfigurationException('Merchant ID is required');
        }

        if (empty($this->merchantPrivateKey)) {
            throw new ConfigurationException('Merchant private key is required');
        }

        if (empty($this->nagadPublicKey)) {
            throw new ConfigurationException('Nagad public key is required');
        }
    }

    /**
     * Validate payment data
     */
    private function validatePaymentData(array $paymentData): void
    {
        $requiredFields = ['order_id', 'amount'];

        foreach ($requiredFields as $field) {
            if (!isset($paymentData[$field]) || empty($paymentData[$field])) {
                throw new ValidationException("Required field '{$field}' is missing or empty");
            }
        }

        if (!is_numeric($paymentData['amount']) || $paymentData['amount'] <= 0) {
            throw new ValidationException('Amount must be a positive number');
        }

        if (strlen($paymentData['order_id']) > 50) {
            throw new ValidationException('Order ID must be 50 characters or less');
        }
    }

    /**
     * Get HTTP client
     */
    private function getHttpClient(): Client
    {
        return $this->httpClient ?? new Client([
            'timeout' => 30,
            'verify' => true,
        ]);
    }

    /**
     * Get base URL based on environment
     */
    private function getBaseUrl(): string
    {
        return $this->sandbox ? self::SANDBOX_BASE_URL : self::PRODUCTION_BASE_URL;
    }

    /**
     * Generate challenge for API calls
     */
    private function generateChallenge(): string
    {
        return bin2hex(random_bytes(16));
    }
}

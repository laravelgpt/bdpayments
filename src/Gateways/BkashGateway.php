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
 * bKash Payment Gateway Implementation
 */
class BkashGateway implements PaymentGatewayInterface
{
    private const GATEWAY_NAME = 'bkash';
    private const SANDBOX_BASE_URL = 'https://tokenized.sandbox.bka.sh/v1.2.0-beta';
    private const PRODUCTION_BASE_URL = 'https://tokenized.pay.bka.sh/v1.2.0-beta';

    private ?string $accessToken = null;
    private ?int $tokenExpiresAt = null;

    public function __construct(
        private readonly string $appKey,
        private readonly string $appSecret,
        private readonly string $username,
        private readonly string $password,
        private readonly bool $sandbox = true,
        private readonly ?Client $httpClient = null
    ) {
        $this->validateConfiguration();
    }

    /**
     * Initialize a payment transaction with bKash
     */
    public function initializePayment(array $paymentData): PaymentResponse
    {
        try {
            $this->validatePaymentData($paymentData);

            $client = $this->getHttpClient();
            $baseUrl = $this->getBaseUrl();

            // Get access token if needed
            $this->ensureValidToken();

            $paymentData = [
                'mode' => '0011',
                'payerReference' => $paymentData['order_id'],
                'callbackURL' => $paymentData['callback_url'] ?? '',
                'amount' => (string) $paymentData['amount'],
                'currency' => $paymentData['currency'] ?? 'BDT',
                'intent' => 'sale',
                'merchantInvoiceNumber' => $paymentData['order_id'],
            ];

            $response = $client->post("{$baseUrl}/payment/create", [
                'json' => $paymentData,
                'headers' => [
                    'Authorization' => $this->accessToken,
                    'X-APP-Key' => $this->appKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if (!$responseData) {
                throw new PaymentException('Invalid response from bKash API');
            }

            if (!isset($responseData['statusCode']) || $responseData['statusCode'] !== '0000') {
                return PaymentResponse::failure(
                    $responseData['statusMessage'] ?? 'Payment initialization failed',
                    $responseData
                );
            }

            return PaymentResponse::success(
                'Payment initialized successfully',
                $responseData,
                $responseData['paymentID'] ?? null,
                $responseData['bkashURL'] ?? null,
                $responseData['paymentID'] ?? null,
                (float) $paymentData['amount'],
                $paymentData['currency'],
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

            // Get access token if needed
            $this->ensureValidToken();

            $response = $client->get("{$baseUrl}/payment/query/{$paymentId}", [
                'headers' => [
                    'Authorization' => $this->accessToken,
                    'X-APP-Key' => $this->appKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if (!$responseData) {
                throw new PaymentException('Invalid response from bKash API');
            }

            if (!isset($responseData['statusCode']) || $responseData['statusCode'] !== '0000') {
                return PaymentResponse::failure(
                    $responseData['statusMessage'] ?? 'Payment verification failed',
                    $responseData
                );
            }

            return PaymentResponse::success(
                'Payment verified successfully',
                $responseData,
                $responseData['paymentID'] ?? null,
                null,
                $responseData['paymentID'] ?? null,
                isset($responseData['amount']) ? (float) $responseData['amount'] : null,
                $responseData['currency'] ?? 'BDT',
                $responseData['transactionStatus'] ?? 'verified'
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

            // Get access token if needed
            $this->ensureValidToken();

            $refundData = [
                'paymentID' => $paymentId,
                'amount' => (string) $amount,
                'trxID' => $paymentId, // Using payment ID as transaction ID
                'sku' => 'refund',
                'reason' => $reason,
            ];

            $response = $client->post("{$baseUrl}/payment/refund", [
                'json' => $refundData,
                'headers' => [
                    'Authorization' => $this->accessToken,
                    'X-APP-Key' => $this->appKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if (!$responseData) {
                throw new PaymentException('Invalid response from bKash API');
            }

            if (!isset($responseData['statusCode']) || $responseData['statusCode'] !== '0000') {
                return PaymentResponse::failure(
                    $responseData['statusMessage'] ?? 'Payment refund failed',
                    $responseData
                );
            }

            return PaymentResponse::success(
                'Payment refunded successfully',
                $responseData,
                $responseData['refundTrxID'] ?? null,
                null,
                $responseData['refundTrxID'] ?? null,
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
        return !empty($this->appKey) && 
               !empty($this->appSecret) && 
               !empty($this->username) && 
               !empty($this->password);
    }

    /**
     * Validate configuration
     */
    private function validateConfiguration(): void
    {
        if (empty($this->appKey)) {
            throw new ConfigurationException('App Key is required');
        }

        if (empty($this->appSecret)) {
            throw new ConfigurationException('App Secret is required');
        }

        if (empty($this->username)) {
            throw new ConfigurationException('Username is required');
        }

        if (empty($this->password)) {
            throw new ConfigurationException('Password is required');
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
     * Ensure we have a valid access token
     */
    private function ensureValidToken(): void
    {
        if ($this->accessToken && $this->tokenExpiresAt && time() < $this->tokenExpiresAt) {
            return;
        }

        $this->getAccessToken();
    }

    /**
     * Get access token from bKash
     */
    private function getAccessToken(): void
    {
        try {
            $client = $this->getHttpClient();
            $baseUrl = $this->getBaseUrl();

            $response = $client->post("{$baseUrl}/tokenized/checkout/token/grant", [
                'json' => [
                    'app_key' => $this->appKey,
                    'app_secret' => $this->appSecret,
                ],
                'headers' => [
                    'username' => $this->username,
                    'password' => $this->password,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if (!$responseData || !isset($responseData['id_token'])) {
                throw new PaymentException('Failed to get access token from bKash');
            }

            $this->accessToken = $responseData['id_token'];
            $this->tokenExpiresAt = time() + ($responseData['expires_in'] ?? 3600) - 60; // 1 minute buffer

        } catch (GuzzleException $e) {
            throw new NetworkException('Network error during token acquisition: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new PaymentException('Unexpected error during token acquisition: ' . $e->getMessage());
        }
    }
}

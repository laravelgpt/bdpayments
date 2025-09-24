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
use Illuminate\Support\Facades\Log;

/**
 * Binance Payment Gateway Implementation
 */
class BinanceGateway implements PaymentGatewayInterface
{
    private const GATEWAY_NAME = 'binance';
    private const SANDBOX_BASE_URL = 'https://testnet.binance.vision';
    private const PRODUCTION_BASE_URL = 'https://api.binance.com';

    private ?string $accessToken = null;
    private ?int $tokenExpiresAt = null;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $secretKey,
        private readonly bool $sandbox = true,
        private readonly ?Client $httpClient = null
    ) {
        $this->validateConfiguration();
    }

    /**
     * Initialize a payment transaction with Binance
     */
    public function initializePayment(array $paymentData): PaymentResponse
    {
        try {
            $this->validatePaymentData($paymentData);

            $client = $this->getHttpClient();
            $baseUrl = $this->getBaseUrl();

            // Get access token if needed
            $this->ensureValidToken();

            $paymentRequest = [
                'merchantTradeNo' => $paymentData['order_id'],
                'totalFee' => (string) $paymentData['amount'],
                'currency' => $paymentData['currency'] ?? 'USDT',
                'productName' => $paymentData['product_name'] ?? 'Payment',
                'productDetail' => $paymentData['product_detail'] ?? '',
                'returnUrl' => $paymentData['return_url'] ?? '',
                'notifyUrl' => $paymentData['notify_url'] ?? '',
                'timestamp' => time() * 1000,
            ];

            $response = $client->post("{$baseUrl}/api/v3/payment/order", [
                'json' => $paymentRequest,
                'headers' => [
                    'X-MBX-APIKEY' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if (!$responseData) {
                throw new PaymentException('Invalid response from Binance API');
            }

            if (!isset($responseData['status']) || $responseData['status'] !== 'SUCCESS') {
                return PaymentResponse::failure(
                    $responseData['msg'] ?? 'Payment initialization failed',
                    $responseData
                );
            }

            return PaymentResponse::success(
                'Payment initialized successfully',
                $responseData,
                $responseData['prepayId'] ?? null,
                $responseData['qrCodeUrl'] ?? null,
                $responseData['prepayId'] ?? null,
                (float) $paymentData['amount'],
                $paymentData['currency'] ?? 'USDT',
                'pending'
            );

        } catch (GuzzleException $e) {
            Log::error('Binance payment initialization network error', [
                'error' => $e->getMessage(),
                'payment_data' => $paymentData
            ]);
            throw new NetworkException('Network error during payment initialization: ' . $e->getMessage());
        } catch (PaymentException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Binance payment initialization unexpected error', [
                'error' => $e->getMessage(),
                'payment_data' => $paymentData
            ]);
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

            $response = $client->get("{$baseUrl}/api/v3/payment/query", [
                'query' => [
                    'prepayId' => $paymentId,
                    'timestamp' => time() * 1000,
                ],
                'headers' => [
                    'X-MBX-APIKEY' => $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if (!$responseData) {
                throw new PaymentException('Invalid response from Binance API');
            }

            if (!isset($responseData['status']) || $responseData['status'] !== 'SUCCESS') {
                return PaymentResponse::failure(
                    $responseData['msg'] ?? 'Payment verification failed',
                    $responseData
                );
            }

            return PaymentResponse::success(
                'Payment verified successfully',
                $responseData,
                $responseData['prepayId'] ?? null,
                null,
                $responseData['prepayId'] ?? null,
                isset($responseData['totalFee']) ? (float) $responseData['totalFee'] : null,
                $responseData['currency'] ?? 'USDT',
                $responseData['status'] ?? 'verified'
            );

        } catch (GuzzleException $e) {
            Log::error('Binance payment verification network error', [
                'error' => $e->getMessage(),
                'payment_id' => $paymentId
            ]);
            throw new NetworkException('Network error during payment verification: ' . $e->getMessage());
        } catch (PaymentException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Binance payment verification unexpected error', [
                'error' => $e->getMessage(),
                'payment_id' => $paymentId
            ]);
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
                'prepayId' => $paymentId,
                'refundAmount' => (string) $amount,
                'refundReason' => $reason,
                'timestamp' => time() * 1000,
            ];

            $response = $client->post("{$baseUrl}/api/v3/payment/refund", [
                'json' => $refundData,
                'headers' => [
                    'X-MBX-APIKEY' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if (!$responseData) {
                throw new PaymentException('Invalid response from Binance API');
            }

            if (!isset($responseData['status']) || $responseData['status'] !== 'SUCCESS') {
                return PaymentResponse::failure(
                    $responseData['msg'] ?? 'Payment refund failed',
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
                'USDT',
                'refunded'
            );

        } catch (GuzzleException $e) {
            Log::error('Binance payment refund network error', [
                'error' => $e->getMessage(),
                'payment_id' => $paymentId,
                'amount' => $amount
            ]);
            throw new NetworkException('Network error during payment refund: ' . $e->getMessage());
        } catch (PaymentException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Binance payment refund unexpected error', [
                'error' => $e->getMessage(),
                'payment_id' => $paymentId,
                'amount' => $amount
            ]);
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
        return !empty($this->apiKey) && !empty($this->secretKey);
    }

    /**
     * Validate configuration
     */
    private function validateConfiguration(): void
    {
        if (empty($this->apiKey)) {
            throw new ConfigurationException('API Key is required');
        }

        if (empty($this->secretKey)) {
            throw new ConfigurationException('Secret Key is required');
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

        // Validate currency
        $supportedCurrencies = ['USDT', 'BTC', 'ETH', 'BNB'];
        if (isset($paymentData['currency']) && !in_array($paymentData['currency'], $supportedCurrencies)) {
            throw new ValidationException('Currency must be one of: ' . implode(', ', $supportedCurrencies));
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
     * Get access token from Binance
     */
    private function getAccessToken(): void
    {
        try {
            $client = $this->getHttpClient();
            $baseUrl = $this->getBaseUrl();

            $timestamp = time() * 1000;
            $signature = hash_hmac('sha256', "timestamp={$timestamp}", $this->secretKey);

            $response = $client->post("{$baseUrl}/api/v3/payment/token", [
                'json' => [
                    'timestamp' => $timestamp,
                ],
                'headers' => [
                    'X-MBX-APIKEY' => $this->apiKey,
                    'X-MBX-SIGNATURE' => $signature,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if (!$responseData || !isset($responseData['accessToken'])) {
                throw new PaymentException('Failed to get access token from Binance');
            }

            $this->accessToken = $responseData['accessToken'];
            $this->tokenExpiresAt = time() + ($responseData['expiresIn'] ?? 3600) - 60; // 1 minute buffer

        } catch (GuzzleException $e) {
            Log::error('Binance token acquisition network error', [
                'error' => $e->getMessage()
            ]);
            throw new NetworkException('Network error during token acquisition: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Binance token acquisition unexpected error', [
                'error' => $e->getMessage()
            ]);
            throw new PaymentException('Unexpected error during token acquisition: ' . $e->getMessage());
        }
    }
}

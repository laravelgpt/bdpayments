<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Gateways;

use BDPayments\LaravelPaymentGateway\Contracts\PaymentGatewayInterface;
use BDPayments\LaravelPaymentGateway\Exceptions\ConfigurationException;
use BDPayments\LaravelPaymentGateway\Exceptions\NetworkException;
use BDPayments\LaravelPaymentGateway\Exceptions\PaymentException;
use BDPayments\LaravelPaymentGateway\Exceptions\ValidationException;
use BDPayments\LaravelPaymentGateway\Models\Payment;
use BDPayments\LaravelPaymentGateway\Models\PaymentLog;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SSLCOMMERZGateway implements PaymentGatewayInterface
{
    private Client $client;
    private array $config;
    private string $baseUrl;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->baseUrl = $config['sandbox'] ? 
            'https://sandbox.sslcommerz.com/api/v1' : 
            'https://api.sslcommerz.com/api/v1';
        
        $this->client = new Client([
            'timeout' => 30,
            'verify' => true,
        ]);

        $this->validateConfiguration();
    }

    private function validateConfiguration(): void
    {
        $required = ['store_id', 'store_password', 'api_key'];
        
        foreach ($required as $key) {
            if (empty($this->config[$key])) {
                throw new ConfigurationException("SSLCOMMERZ configuration missing: {$key}");
            }
        }
    }

    public function initializePayment(array $data): array
    {
        $this->validatePaymentData($data);

        try {
            $paymentData = [
                'store_id' => $this->config['store_id'],
                'store_password' => $this->config['store_password'],
                'tran_id' => $data['reference_id'],
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'BDT',
                'desc' => $data['description'] ?? 'Payment',
                'cus_name' => $data['customer']['name'] ?? 'Customer',
                'cus_email' => $data['customer']['email'] ?? '',
                'cus_phone' => $data['customer']['mobile'] ?? '',
                'success_url' => $data['success_url'],
                'fail_url' => $data['cancel_url'],
                'cancel_url' => $data['cancel_url'],
            ];

            $response = $this->client->post($this->baseUrl . '/payment/initialize', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $paymentData,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() === 200 && $responseData['status'] === 'success') {
                $this->logPayment('initialize', $data['reference_id'], $responseData);

                return [
                    'success' => true,
                    'gateway' => 'sslcommerz',
                    'transaction_id' => $responseData['data']['transaction_id'],
                    'redirect_url' => $responseData['data']['payment_url'],
                    'status' => 'pending',
                    'gateway_response' => $responseData,
                ];
            }

            throw new PaymentException($responseData['message'] ?? 'Failed to initialize SSLCOMMERZ payment');
        } catch (GuzzleException $e) {
            Log::error('SSLCOMMERZ payment initialization failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw new NetworkException('SSLCOMMERZ payment initialization failed: ' . $e->getMessage());
        }
    }

    public function verifyPayment(string $transactionId, array $data = []): array
    {
        try {
            $response = $this->client->get($this->baseUrl . '/payment/verify/' . $transactionId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                    'Accept' => 'application/json',
                ],
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() === 200) {
                $status = $this->mapSSLCOMMERZStatus($responseData['data']['status'] ?? 'PENDING');
                
                $this->logPayment('verify', $transactionId, $responseData);

                return [
                    'success' => $status === 'completed',
                    'gateway' => 'sslcommerz',
                    'transaction_id' => $transactionId,
                    'status' => $status,
                    'amount' => $responseData['data']['amount'] ?? 0,
                    'currency' => $responseData['data']['currency'] ?? 'BDT',
                    'gateway_response' => $responseData,
                ];
            }

            throw new PaymentException('Failed to verify SSLCOMMERZ payment');
        } catch (GuzzleException $e) {
            Log::error('SSLCOMMERZ payment verification failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);
            throw new NetworkException('SSLCOMMERZ payment verification failed: ' . $e->getMessage());
        }
    }

    public function refundPayment(string $transactionId, ?float $amount = null, string $reason = ''): array
    {
        try {
            $refundData = [
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'reason' => $reason ?: 'Refund request',
            ];

            $response = $this->client->post($this->baseUrl . '/payment/refund', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                    'Content-Type' => 'application/json',
                ],
                'json' => $refundData,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() === 200 && $responseData['status'] === 'success') {
                $this->logPayment('refund', $transactionId, $responseData);

                return [
                    'success' => true,
                    'gateway' => 'sslcommerz',
                    'transaction_id' => $transactionId,
                    'refund_id' => $responseData['data']['refund_id'] ?? null,
                    'status' => 'refunded',
                    'amount' => $responseData['data']['refunded_amount'] ?? $amount,
                    'gateway_response' => $responseData,
                ];
            }

            throw new PaymentException($responseData['message'] ?? 'Failed to refund SSLCOMMERZ payment');
        } catch (GuzzleException $e) {
            Log::error('SSLCOMMERZ payment refund failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);
            throw new NetworkException('SSLCOMMERZ payment refund failed: ' . $e->getMessage());
        }
    }

    public function getPaymentStatus(string $transactionId): array
    {
        return $this->verifyPayment($transactionId);
    }

    private function validatePaymentData(array $data): void
    {
        $validator = Validator::make($data, [
            'amount' => 'required|numeric|min:1',
            'reference_id' => 'required|string|max:255',
            'currency' => 'string|max:3',
            'description' => 'string|max:255',
            'success_url' => 'required|url',
            'cancel_url' => 'required|url',
            'customer.name' => 'string|max:255',
            'customer.mobile' => 'string|max:20',
            'customer.email' => 'email',
        ]);

        if ($validator->fails()) {
            throw new ValidationException('Invalid payment data: ' . implode(', ', $validator->errors()->all()));
        }
    }

    private function mapSSLCOMMERZStatus(string $status): string
    {
        return match ($status) {
            'PENDING' => 'pending',
            'PROCESSING' => 'pending',
            'SUCCESS' => 'completed',
            'COMPLETED' => 'completed',
            'FAILED' => 'failed',
            'CANCELLED' => 'cancelled',
            'REFUNDED' => 'refunded',
            default => 'unknown',
        };
    }

    private function logPayment(string $action, string $transactionId, array $data): void
    {
        PaymentLog::create([
            'gateway' => 'sslcommerz',
            'action' => $action,
            'transaction_id' => $transactionId,
            'status' => 'success',
            'request_data' => [],
            'response_data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function getSupportedCurrencies(): array
    {
        return ['BDT', 'USD'];
    }

    public function getGatewayName(): string
    {
        return 'SSLCOMMERZ';
    }

    public function isTestMode(): bool
    {
        return $this->config['sandbox'] ?? true;
    }
}

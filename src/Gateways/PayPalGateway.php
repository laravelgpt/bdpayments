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

class PayPalGateway implements PaymentGatewayInterface
{
    private Client $client;
    private array $config;
    private string $accessToken;
    private ?int $tokenExpiresAt = null;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->client = new Client([
            'timeout' => 30,
            'verify' => true,
        ]);

        $this->validateConfiguration();
    }

    private function validateConfiguration(): void
    {
        $required = ['client_id', 'client_secret', 'mode'];
        
        foreach ($required as $key) {
            if (empty($this->config[$key])) {
                throw new ConfigurationException("PayPal configuration missing: {$key}");
            }
        }

        if (!in_array($this->config['mode'], ['sandbox', 'live'])) {
            throw new ConfigurationException('PayPal mode must be either "sandbox" or "live"');
        }
    }

    private function getBaseUrl(): string
    {
        return $this->config['mode'] === 'live' 
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    private function getAccessToken(): string
    {
        if ($this->accessToken && $this->tokenExpiresAt && time() < $this->tokenExpiresAt) {
            return $this->accessToken;
        }

        try {
            $response = $this->client->post($this->getBaseUrl() . '/v1/oauth2/token', [
                'auth' => [$this->config['client_id'], $this->config['client_secret']],
                'form_params' => [
                    'grant_type' => 'client_credentials',
                ],
                'headers' => [
                    'Accept' => 'application/json',
                    'Accept-Language' => 'en_US',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['access_token'])) {
                $this->accessToken = $data['access_token'];
                $this->tokenExpiresAt = time() + ($data['expires_in'] ?? 3600) - 60; // 1 minute buffer
                return $this->accessToken;
            }

            throw new PaymentException('Failed to obtain PayPal access token');
        } catch (GuzzleException $e) {
            Log::error('PayPal token request failed', ['error' => $e->getMessage()]);
            throw new NetworkException('Failed to authenticate with PayPal: ' . $e->getMessage());
        }
    }

    public function initializePayment(array $data): array
    {
        $this->validatePaymentData($data);

        try {
            $accessToken = $this->getAccessToken();

            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => $data['reference_id'],
                        'amount' => [
                            'currency_code' => $data['currency'] ?? 'USD',
                            'value' => number_format($data['amount'], 2, '.', ''),
                        ],
                        'description' => $data['description'] ?? 'Payment',
                    ],
                ],
                'application_context' => [
                    'brand_name' => $this->config['brand_name'] ?? 'BD Payments',
                    'landing_page' => 'NO_PREFERENCE',
                    'user_action' => 'PAY_NOW',
                    'return_url' => $data['success_url'],
                    'cancel_url' => $data['cancel_url'],
                ],
            ];

            // Add customer information if provided
            if (isset($data['customer'])) {
                $orderData['payer'] = [
                    'name' => [
                        'given_name' => $data['customer']['first_name'] ?? '',
                        'surname' => $data['customer']['last_name'] ?? '',
                    ],
                    'email_address' => $data['customer']['email'] ?? '',
                ];
            }

            $response = $this->client->post($this->getBaseUrl() . '/v2/checkout/orders', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $accessToken,
                    'PayPal-Request-Id' => $data['reference_id'],
                ],
                'json' => $orderData,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() === 201 && isset($responseData['id'])) {
                $this->logPayment('initialize', $data['reference_id'], $responseData);

                return [
                    'success' => true,
                    'gateway' => 'paypal',
                    'transaction_id' => $responseData['id'],
                    'redirect_url' => $this->getApprovalUrl($responseData),
                    'status' => 'pending',
                    'gateway_response' => $responseData,
                ];
            }

            throw new PaymentException('Failed to create PayPal order');
        } catch (GuzzleException $e) {
            Log::error('PayPal payment initialization failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw new NetworkException('PayPal payment initialization failed: ' . $e->getMessage());
        }
    }

    public function verifyPayment(string $transactionId, array $data = []): array
    {
        try {
            $accessToken = $this->getAccessToken();

            $response = $this->client->get($this->getBaseUrl() . '/v2/checkout/orders/' . $transactionId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
            ]);

            $orderData = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() === 200) {
                $status = $this->mapPayPalStatus($orderData['status'] ?? 'UNKNOWN');
                
                $this->logPayment('verify', $transactionId, $orderData);

                return [
                    'success' => $status === 'completed',
                    'gateway' => 'paypal',
                    'transaction_id' => $transactionId,
                    'status' => $status,
                    'amount' => $this->extractAmount($orderData),
                    'currency' => $this->extractCurrency($orderData),
                    'gateway_response' => $orderData,
                ];
            }

            throw new PaymentException('Failed to verify PayPal payment');
        } catch (GuzzleException $e) {
            Log::error('PayPal payment verification failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);
            throw new NetworkException('PayPal payment verification failed: ' . $e->getMessage());
        }
    }

    public function capturePayment(string $transactionId, ?float $amount = null): array
    {
        try {
            $accessToken = $this->getAccessToken();

            $captureData = [
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => $amount ? number_format($amount, 2, '.', '') : null,
                ],
            ];

            $response = $this->client->post($this->getBaseUrl() . '/v2/checkout/orders/' . $transactionId . '/capture', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'json' => $captureData,
            ]);

            $captureResult = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() === 201) {
                $this->logPayment('capture', $transactionId, $captureResult);

                return [
                    'success' => true,
                    'gateway' => 'paypal',
                    'transaction_id' => $transactionId,
                    'capture_id' => $captureResult['purchase_units'][0]['payments']['captures'][0]['id'] ?? null,
                    'status' => 'completed',
                    'gateway_response' => $captureResult,
                ];
            }

            throw new PaymentException('Failed to capture PayPal payment');
        } catch (GuzzleException $e) {
            Log::error('PayPal payment capture failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);
            throw new NetworkException('PayPal payment capture failed: ' . $e->getMessage());
        }
    }

    public function refundPayment(string $transactionId, ?float $amount = null, string $reason = ''): array
    {
        try {
            $accessToken = $this->getAccessToken();

            // First, get the capture ID from the transaction
            $orderResponse = $this->client->get($this->getBaseUrl() . '/v2/checkout/orders/' . $transactionId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]);

            $orderData = json_decode($orderResponse->getBody()->getContents(), true);
            $captureId = $this->extractCaptureId($orderData);

            if (!$captureId) {
                throw new PaymentException('No capture ID found for refund');
            }

            $refundData = [
                'amount' => [
                    'currency_code' => $this->extractCurrency($orderData),
                    'value' => $amount ? number_format($amount, 2, '.', '') : $this->extractAmount($orderData),
                ],
                'note_to_payer' => $reason ?: 'Refund request',
            ];

            $response = $this->client->post($this->getBaseUrl() . '/v2/payments/captures/' . $captureId . '/refund', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'json' => $refundData,
            ]);

            $refundResult = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() === 201) {
                $this->logPayment('refund', $transactionId, $refundResult);

                return [
                    'success' => true,
                    'gateway' => 'paypal',
                    'transaction_id' => $transactionId,
                    'refund_id' => $refundResult['id'] ?? null,
                    'status' => 'refunded',
                    'amount' => $refundResult['amount']['value'] ?? null,
                    'gateway_response' => $refundResult,
                ];
            }

            throw new PaymentException('Failed to refund PayPal payment');
        } catch (GuzzleException $e) {
            Log::error('PayPal payment refund failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);
            throw new NetworkException('PayPal payment refund failed: ' . $e->getMessage());
        }
    }

    public function getPaymentStatus(string $transactionId): array
    {
        return $this->verifyPayment($transactionId);
    }

    private function validatePaymentData(array $data): void
    {
        $validator = Validator::make($data, [
            'amount' => 'required|numeric|min:0.01',
            'reference_id' => 'required|string|max:255',
            'currency' => 'string|max:3',
            'description' => 'string|max:255',
            'success_url' => 'required|url',
            'cancel_url' => 'required|url',
            'customer.email' => 'email',
        ]);

        if ($validator->fails()) {
            throw new ValidationException('Invalid payment data: ' . implode(', ', $validator->errors()->all()));
        }
    }

    private function getApprovalUrl(array $orderData): string
    {
        foreach ($orderData['links'] ?? [] as $link) {
            if ($link['rel'] === 'approve') {
                return $link['href'];
            }
        }

        throw new PaymentException('No approval URL found in PayPal response');
    }

    private function mapPayPalStatus(string $status): string
    {
        return match ($status) {
            'CREATED' => 'pending',
            'SAVED' => 'pending',
            'APPROVED' => 'pending',
            'VOIDED' => 'failed',
            'COMPLETED' => 'completed',
            'PAYER_ACTION_REQUIRED' => 'pending',
            default => 'unknown',
        };
    }

    private function extractAmount(array $orderData): float
    {
        $amount = $orderData['purchase_units'][0]['amount']['value'] ?? 0;
        return (float) $amount;
    }

    private function extractCurrency(array $orderData): string
    {
        return $orderData['purchase_units'][0]['amount']['currency_code'] ?? 'USD';
    }

    private function extractCaptureId(array $orderData): ?string
    {
        return $orderData['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;
    }

    private function logPayment(string $action, string $transactionId, array $data): void
    {
        PaymentLog::create([
            'gateway' => 'paypal',
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
        return [
            'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'CHF', 'NOK', 'DKK', 'SEK',
            'PLN', 'CZK', 'HUF', 'BGN', 'RON', 'HRK', 'RUB', 'TRY', 'BRL', 'MXN',
            'ARS', 'CLP', 'COP', 'PEN', 'UYU', 'VEF', 'ZAR', 'HKD', 'SGD', 'TWD',
            'THB', 'MYR', 'PHP', 'IDR', 'VND', 'KRW', 'INR', 'PKR', 'LKR', 'BDT',
            'NPR', 'MMK', 'KHR', 'LAK', 'MOP', 'MNT', 'NZD', 'FJD', 'PGK', 'SBD',
            'TOP', 'VUV', 'WST', 'XPF', 'ZWL', 'AED', 'BHD', 'JOD', 'KWD', 'LBP',
            'OMR', 'QAR', 'SAR', 'YER', 'EGP', 'ILS', 'JMD', 'BBD', 'BZD', 'TTD',
            'XCD', 'AWG', 'BMD', 'KYD', 'DOP', 'GTQ', 'HNL', 'NIO', 'PAB', 'PYG',
            'SVC', 'UAH', 'BYN', 'GEL', 'AMD', 'AZN', 'KZT', 'KGS', 'TJS', 'TMT',
            'UZS', 'MAD', 'TND', 'DZD', 'LBP', 'LYD', 'MRO', 'MUR', 'SCR', 'SLL',
            'SOS', 'TZS', 'UGX', 'ZMW', 'BWP', 'LSL', 'NAD', 'SZL', 'AOA', 'CDF',
            'GMD', 'GNF', 'LRD', 'MWK', 'MZN', 'NGN', 'RWF', 'STD', 'XAF', 'XOF',
            'ZAR', 'ETB', 'KES', 'MGA', 'MVR', 'NPR', 'PKR', 'LKR', 'BDT', 'BTN',
            'AFN', 'IRR', 'IQD', 'LBP', 'JOD', 'KWD', 'OMR', 'QAR', 'SAR', 'YER',
        ];
    }

    public function getGatewayName(): string
    {
        return 'PayPal';
    }

    public function isTestMode(): bool
    {
        return $this->config['mode'] === 'sandbox';
    }
}

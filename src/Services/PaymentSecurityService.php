<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Services;

use BDPayments\LaravelPaymentGateway\Exceptions\PaymentException;
use BDPayments\LaravelPaymentGateway\Exceptions\ValidationException;
use BDPayments\LaravelPaymentGateway\Models\Payment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentSecurityService
{
    private array $config;

    public function __construct()
    {
        $this->config = config('payment-gateway.security', []);
    }

    /**
     * Encrypt sensitive payment data
     */
    public function encryptPaymentData(array $data): array
    {
        $sensitiveFields = [
            'card_number', 'cvv', 'pin', 'otp', 'password', 'secret_key',
            'access_token', 'refresh_token', 'bank_account', 'routing_number',
            'ssn', 'tax_id', 'personal_id', 'phone_number', 'email'
        ];

        $encrypted = $data;

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $encrypted[$field] = $this->encryptField($data[$field]);
            }
        }

        return $encrypted;
    }

    /**
     * Decrypt sensitive payment data
     */
    public function decryptPaymentData(array $data): array
    {
        $sensitiveFields = [
            'card_number', 'cvv', 'pin', 'otp', 'password', 'secret_key',
            'access_token', 'refresh_token', 'bank_account', 'routing_number',
            'ssn', 'tax_id', 'personal_id', 'phone_number', 'email'
        ];

        $decrypted = $data;

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field]) && $this->isEncrypted($data[$field])) {
                try {
                    $decrypted[$field] = $this->decryptField($data[$field]);
                } catch (\Exception $e) {
                    Log::warning('Failed to decrypt field', ['field' => $field, 'error' => $e->getMessage()]);
                    $decrypted[$field] = '[ENCRYPTED]';
                }
            }
        }

        return $decrypted;
    }

    /**
     * Generate secure payment hash for tampering protection
     */
    public function generatePaymentHash(array $data, string $secret = null): string
    {
        $secret = $secret ?: $this->config['hash_secret'] ?? config('app.key');
        
        // Remove sensitive fields from hash calculation
        $hashData = $this->removeSensitiveFields($data);
        
        // Sort array to ensure consistent hashing
        ksort($hashData);
        
        $hashString = http_build_query($hashData) . $secret;
        
        return hash('sha256', $hashString);
    }

    /**
     * Verify payment hash to detect tampering
     */
    public function verifyPaymentHash(array $data, string $hash, string $secret = null): bool
    {
        $expectedHash = $this->generatePaymentHash($data, $secret);
        return hash_equals($expectedHash, $hash);
    }

    /**
     * Generate secure transaction ID
     */
    public function generateSecureTransactionId(): string
    {
        $prefix = $this->config['transaction_prefix'] ?? 'TXN';
        $timestamp = time();
        $random = Str::random(16);
        
        return strtoupper($prefix . '_' . $timestamp . '_' . $random);
    }

    /**
     * Generate secure reference ID
     */
    public function generateSecureReferenceId(): string
    {
        $prefix = $this->config['reference_prefix'] ?? 'REF';
        $timestamp = time();
        $random = Str::random(12);
        
        return strtoupper($prefix . '_' . $timestamp . '_' . $random);
    }

    /**
     * Validate payment data integrity
     */
    public function validatePaymentIntegrity(Payment $payment, array $data): bool
    {
        // Check if payment hash matches
        if (isset($data['payment_hash'])) {
            $calculatedHash = $this->generatePaymentHash([
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'reference_id' => $payment->reference_id,
                'gateway' => $payment->gateway,
            ]);
            
            if (!$this->verifyPaymentHash([
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'reference_id' => $payment->reference_id,
                'gateway' => $payment->gateway,
            ], $data['payment_hash'])) {
                Log::warning('Payment hash verification failed', [
                    'payment_id' => $payment->id,
                    'expected_hash' => $calculatedHash,
                    'provided_hash' => $data['payment_hash'],
                ]);
                return false;
            }
        }

        // Check amount integrity
        if (isset($data['amount']) && abs($data['amount'] - $payment->amount) > 0.01) {
            Log::warning('Payment amount mismatch detected', [
                'payment_id' => $payment->id,
                'stored_amount' => $payment->amount,
                'provided_amount' => $data['amount'],
            ]);
            return false;
        }

        // Check currency integrity
        if (isset($data['currency']) && $data['currency'] !== $payment->currency) {
            Log::warning('Payment currency mismatch detected', [
                'payment_id' => $payment->id,
                'stored_currency' => $payment->currency,
                'provided_currency' => $data['currency'],
            ]);
            return false;
        }

        return true;
    }

    /**
     * Rate limiting for payment attempts
     */
    public function checkRateLimit(string $identifier, int $maxAttempts = 5, int $windowMinutes = 15): bool
    {
        $key = "payment_rate_limit:{$identifier}";
        $attempts = Cache::get($key, 0);

        if ($attempts >= $maxAttempts) {
            Log::warning('Payment rate limit exceeded', [
                'identifier' => $identifier,
                'attempts' => $attempts,
                'max_attempts' => $maxAttempts,
            ]);
            return false;
        }

        Cache::put($key, $attempts + 1, now()->addMinutes($windowMinutes));
        return true;
    }

    /**
     * IP-based fraud detection
     */
    public function detectFraudulentActivity(string $ipAddress, array $paymentData): array
    {
        $fraudIndicators = [];

        // Check for suspicious IP patterns
        if ($this->isSuspiciousIP($ipAddress)) {
            $fraudIndicators[] = 'suspicious_ip';
        }

        // Check for rapid payment attempts
        if ($this->hasRapidPayments($ipAddress)) {
            $fraudIndicators[] = 'rapid_payments';
        }

        // Check for unusual amounts
        if ($this->hasUnusualAmount($paymentData['amount'] ?? 0)) {
            $fraudIndicators[] = 'unusual_amount';
        }

        // Check for suspicious user agent
        if ($this->hasSuspiciousUserAgent()) {
            $fraudIndicators[] = 'suspicious_user_agent';
        }

        return $fraudIndicators;
    }

    /**
     * Generate secure webhook signature
     */
    public function generateWebhookSignature(string $payload, string $secret = null): string
    {
        $secret = $secret ?: $this->config['webhook_secret'] ?? config('app.key');
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $signature, string $secret = null): bool
    {
        $expectedSignature = $this->generateWebhookSignature($payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Sanitize payment data for logging
     */
    public function sanitizeForLogging(array $data): array
    {
        $sensitiveFields = [
            'card_number', 'cvv', 'pin', 'otp', 'password', 'secret_key',
            'access_token', 'refresh_token', 'bank_account', 'routing_number',
            'ssn', 'tax_id', 'personal_id', 'phone_number', 'email'
        ];

        $sanitized = $data;

        foreach ($sensitiveFields as $field) {
            if (isset($sanitized[$field])) {
                $sanitized[$field] = $this->maskSensitiveData($sanitized[$field]);
            }
        }

        return $sanitized;
    }

    /**
     * Encrypt a single field
     */
    private function encryptField(string $value): string
    {
        return Crypt::encryptString($value);
    }

    /**
     * Decrypt a single field
     */
    private function decryptField(string $value): string
    {
        return Crypt::decryptString($value);
    }

    /**
     * Check if a value is encrypted
     */
    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Remove sensitive fields from data
     */
    private function removeSensitiveFields(array $data): array
    {
        $sensitiveFields = [
            'card_number', 'cvv', 'pin', 'otp', 'password', 'secret_key',
            'access_token', 'refresh_token', 'bank_account', 'routing_number',
            'ssn', 'tax_id', 'personal_id', 'phone_number', 'email'
        ];

        return array_diff_key($data, array_flip($sensitiveFields));
    }

    /**
     * Check if IP is suspicious
     */
    private function isSuspiciousIP(string $ipAddress): bool
    {
        // Check against known VPN/proxy databases
        $suspiciousIPs = Cache::get('suspicious_ips', []);
        
        if (in_array($ipAddress, $suspiciousIPs)) {
            return true;
        }

        // Check for Tor exit nodes (simplified check)
        if ($this->isTorExitNode($ipAddress)) {
            return true;
        }

        return false;
    }

    /**
     * Check for rapid payment attempts
     */
    private function hasRapidPayments(string $ipAddress): bool
    {
        $key = "rapid_payments:{$ipAddress}";
        $attempts = Cache::get($key, 0);
        
        return $attempts > 10; // More than 10 attempts in the window
    }

    /**
     * Check for unusual payment amounts
     */
    private function hasUnusualAmount(float $amount): bool
    {
        $maxAmount = $this->config['max_amount'] ?? 10000;
        $minAmount = $this->config['min_amount'] ?? 0.01;
        
        return $amount > $maxAmount || $amount < $minAmount;
    }

    /**
     * Check for suspicious user agent
     */
    private function hasSuspiciousUserAgent(): bool
    {
        $userAgent = request()->userAgent();
        
        if (empty($userAgent)) {
            return true;
        }

        $suspiciousPatterns = [
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget',
            'python', 'php', 'java', 'perl', 'ruby'
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP is a Tor exit node (simplified)
     */
    private function isTorExitNode(string $ipAddress): bool
    {
        // This is a simplified check. In production, you'd use a proper Tor exit node list
        $torExitNodes = Cache::get('tor_exit_nodes', []);
        return in_array($ipAddress, $torExitNodes);
    }

    /**
     * Mask sensitive data for logging
     */
    private function maskSensitiveData(string $data): string
    {
        $length = strlen($data);
        
        if ($length <= 4) {
            return str_repeat('*', $length);
        }
        
        $visible = 2;
        $masked = $length - $visible;
        
        return substr($data, 0, $visible) . str_repeat('*', $masked);
    }

    /**
     * Generate secure nonce for CSRF protection
     */
    public function generateNonce(): string
    {
        return Str::random(32);
    }

    /**
     * Verify nonce for CSRF protection
     */
    public function verifyNonce(string $nonce): bool
    {
        $key = "payment_nonce:{$nonce}";
        $exists = Cache::has($key);
        
        if ($exists) {
            Cache::forget($key); // Use once
        }
        
        return $exists;
    }

    /**
     * Store nonce for verification
     */
    public function storeNonce(string $nonce, int $expiryMinutes = 30): void
    {
        $key = "payment_nonce:{$nonce}";
        Cache::put($key, true, now()->addMinutes($expiryMinutes));
    }
}

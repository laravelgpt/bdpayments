<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Services;

use BDPayments\LaravelPaymentGateway\PaymentResponse;
use BDPayments\LaravelPaymentGateway\Exceptions\PaymentException;
use Illuminate\Support\Facades\Log;

class PaymentLogger
{
    private array $logs = [];
    private bool $enabled;

    public function __construct(bool $enabled = true)
    {
        $this->enabled = $enabled;
    }

    /**
     * Log payment operation
     */
    public function log(
        string $operation,
        string $gateway,
        array $data = [],
        ?PaymentResponse $response = null,
        ?PaymentException $exception = null
    ): void {
        if (!$this->enabled) {
            return;
        }

        $logEntry = [
            'timestamp' => now()->toISOString(),
            'operation' => $operation,
            'gateway' => $gateway,
            'data' => $this->sanitizeData($data),
            'success' => $response?->success ?? false,
            'message' => $response?->message ?? ($exception?->getMessage() ?? ''),
            'payment_id' => $response?->paymentId,
            'transaction_id' => $response?->transactionId,
            'amount' => $response?->amount,
            'currency' => $response?->currency,
            'status' => $response?->status,
            'http_code' => $response?->httpCode,
            'exception' => $exception ? [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'context' => $exception->getContext(),
            ] : null,
        ];

        $this->logs[] = $logEntry;

        // Also log to Laravel's log system
        if ($response?->success) {
            Log::info("Payment {$operation} successful", $logEntry);
        } else {
            Log::warning("Payment {$operation} failed", $logEntry);
        }
    }

    /**
     * Log payment initialization
     */
    public function logPaymentInitialization(
        string $gateway,
        array $paymentData,
        PaymentResponse $response,
        ?PaymentException $exception = null
    ): void {
        $this->log('initialize_payment', $gateway, $paymentData, $response, $exception);
    }

    /**
     * Log payment verification
     */
    public function logPaymentVerification(
        string $gateway,
        string $paymentId,
        PaymentResponse $response,
        ?PaymentException $exception = null
    ): void {
        $this->log('verify_payment', $gateway, ['payment_id' => $paymentId], $response, $exception);
    }

    /**
     * Log payment refund
     */
    public function logPaymentRefund(
        string $gateway,
        string $paymentId,
        float $amount,
        string $reason,
        PaymentResponse $response,
        ?PaymentException $exception = null
    ): void {
        $this->log('refund_payment', $gateway, [
            'payment_id' => $paymentId,
            'amount' => $amount,
            'reason' => $reason,
        ], $response, $exception);
    }

    /**
     * Log payment status check
     */
    public function logPaymentStatus(
        string $gateway,
        string $paymentId,
        PaymentResponse $response,
        ?PaymentException $exception = null
    ): void {
        $this->log('get_payment_status', $gateway, ['payment_id' => $paymentId], $response, $exception);
    }

    /**
     * Get all logs
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    /**
     * Get logs by gateway
     */
    public function getLogsByGateway(string $gateway): array
    {
        return array_filter($this->logs, fn($log) => $log['gateway'] === $gateway);
    }

    /**
     * Get logs by operation
     */
    public function getLogsByOperation(string $operation): array
    {
        return array_filter($this->logs, fn($log) => $log['operation'] === $operation);
    }

    /**
     * Get logs by payment ID
     */
    public function getLogsByPaymentId(string $paymentId): array
    {
        return array_filter($this->logs, fn($log) => $log['payment_id'] === $paymentId);
    }

    /**
     * Clear logs
     */
    public function clearLogs(): void
    {
        $this->logs = [];
    }

    /**
     * Get logs as JSON
     */
    public function getLogsAsJson(): string
    {
        return json_encode($this->logs, JSON_PRETTY_PRINT);
    }

    /**
     * Export logs to file
     */
    public function exportToFile(string $filePath): bool
    {
        return file_put_contents($filePath, $this->getLogsAsJson()) !== false;
    }

    /**
     * Enable logging
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable logging
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Check if logging is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Sanitize sensitive data
     */
    private function sanitizeData(array $data): array
    {
        $sensitiveFields = [
            'merchant_private_key',
            'app_secret',
            'password',
            'secret_key',
            'token',
            'secret',
        ];

        $sanitized = $data;

        foreach ($sensitiveFields as $field) {
            if (isset($sanitized[$field])) {
                $sanitized[$field] = '***REDACTED***';
            }
        }

        return $sanitized;
    }
}

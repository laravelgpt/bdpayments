<?php

declare(strict_types=1);

namespace BDPayments\NagadBkashGateway\Validators;

use BDPayments\NagadBkashGateway\Exceptions\ValidationException;

/**
 * Payment data validator
 */
class PaymentValidator
{
    /**
     * Validate payment initialization data
     *
     * @param array $data
     * @return bool
     * @throws ValidationException
     */
    public static function validatePaymentData(array $data): bool
    {
        $requiredFields = ['order_id', 'amount'];
        
        // Check required fields
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new ValidationException("Required field '{$field}' is missing or empty");
            }
        }

        // Validate amount
        if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            throw new ValidationException('Amount must be a positive number');
        }

        // Validate order ID
        if (strlen($data['order_id']) > 50) {
            throw new ValidationException('Order ID must be 50 characters or less');
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $data['order_id'])) {
            throw new ValidationException('Order ID contains invalid characters');
        }

        // Validate currency if provided
        if (isset($data['currency'])) {
            if (!in_array($data['currency'], ['BDT', 'USD'], true)) {
                throw new ValidationException('Currency must be BDT or USD');
            }
        }

        // Validate callback URL if provided
        if (isset($data['callback_url'])) {
            if (!filter_var($data['callback_url'], FILTER_VALIDATE_URL)) {
                throw new ValidationException('Invalid callback URL format');
            }
        }

        return true;
    }

    /**
     * Validate payment ID
     *
     * @param string $paymentId
     * @return bool
     * @throws ValidationException
     */
    public static function validatePaymentId(string $paymentId): bool
    {
        if (empty($paymentId)) {
            throw new ValidationException('Payment ID cannot be empty');
        }

        if (strlen($paymentId) > 100) {
            throw new ValidationException('Payment ID must be 100 characters or less');
        }

        return true;
    }

    /**
     * Validate refund data
     *
     * @param string $paymentId
     * @param float $amount
     * @param string $reason
     * @return bool
     * @throws ValidationException
     */
    public static function validateRefundData(string $paymentId, float $amount, string $reason = ''): bool
    {
        self::validatePaymentId($paymentId);

        if ($amount <= 0) {
            throw new ValidationException('Refund amount must be positive');
        }

        if (strlen($reason) > 255) {
            throw new ValidationException('Refund reason must be 255 characters or less');
        }

        return true;
    }

    /**
     * Validate gateway configuration
     *
     * @param string $gateway
     * @param array $config
     * @return bool
     * @throws ValidationException
     */
    public static function validateGatewayConfig(string $gateway, array $config): bool
    {
        $gateway = strtolower($gateway);

        switch ($gateway) {
            case 'nagad':
                return self::validateNagadConfig($config);
            case 'bkash':
                return self::validateBkashConfig($config);
            default:
                throw new ValidationException("Unsupported gateway: {$gateway}");
        }
    }

    /**
     * Validate Nagad configuration
     *
     * @param array $config
     * @return bool
     * @throws ValidationException
     */
    private static function validateNagadConfig(array $config): bool
    {
        $requiredFields = ['merchant_id', 'merchant_private_key', 'nagad_public_key'];

        foreach ($requiredFields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                throw new ValidationException("Missing required Nagad configuration: {$field}");
            }
        }

        // Validate merchant ID format
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $config['merchant_id'])) {
            throw new ValidationException('Invalid merchant ID format');
        }

        return true;
    }

    /**
     * Validate bKash configuration
     *
     * @param array $config
     * @return bool
     * @throws ValidationException
     */
    private static function validateBkashConfig(array $config): bool
    {
        $requiredFields = ['app_key', 'app_secret', 'username', 'password'];

        foreach ($requiredFields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                throw new ValidationException("Missing required bKash configuration: {$field}");
            }
        }

        return true;
    }

    /**
     * Sanitize payment data
     *
     * @param array $data
     * @return array
     */
    public static function sanitizePaymentData(array $data): array
    {
        $sanitized = [];

        // Sanitize order ID
        if (isset($data['order_id'])) {
            $sanitized['order_id'] = trim($data['order_id']);
        }

        // Sanitize amount
        if (isset($data['amount'])) {
            $sanitized['amount'] = (float) $data['amount'];
        }

        // Sanitize currency
        if (isset($data['currency'])) {
            $sanitized['currency'] = strtoupper(trim($data['currency']));
        }

        // Sanitize callback URL
        if (isset($data['callback_url'])) {
            $sanitized['callback_url'] = filter_var($data['callback_url'], FILTER_SANITIZE_URL);
        }

        // Sanitize other fields
        $allowedFields = ['order_id', 'amount', 'currency', 'callback_url', 'description', 'customer_name', 'customer_phone', 'customer_email'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if (is_string($data[$field])) {
                    $sanitized[$field] = trim($data[$field]);
                } else {
                    $sanitized[$field] = $data[$field];
                }
            }
        }

        return $sanitized;
    }
}

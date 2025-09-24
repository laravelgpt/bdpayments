<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway;

/**
 * Payment response data structure
 */
class PaymentResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly array $data = [],
        public readonly ?string $paymentId = null,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $transactionId = null,
        public readonly ?float $amount = null,
        public readonly ?string $currency = null,
        public readonly ?string $status = null,
        public readonly ?int $httpCode = null
    ) {
    }

    /**
     * Create a successful response
     */
    public static function success(
        string $message,
        array $data = [],
        ?string $paymentId = null,
        ?string $redirectUrl = null,
        ?string $transactionId = null,
        ?float $amount = null,
        ?string $currency = null,
        ?string $status = null
    ): self {
        return new self(
            success: true,
            message: $message,
            data: $data,
            paymentId: $paymentId,
            redirectUrl: $redirectUrl,
            transactionId: $transactionId,
            amount: $amount,
            currency: $currency,
            status: $status
        );
    }

    /**
     * Create a failed response
     */
    public static function failure(
        string $message,
        array $data = [],
        ?int $httpCode = null
    ): self {
        return new self(
            success: false,
            message: $message,
            data: $data,
            httpCode: $httpCode
        );
    }

    /**
     * Get response as array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
            'payment_id' => $this->paymentId,
            'redirect_url' => $this->redirectUrl,
            'transaction_id' => $this->transactionId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'http_code' => $this->httpCode,
        ];
    }

    /**
     * Get response as JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
}
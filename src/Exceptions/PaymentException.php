<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Exceptions;

use Exception;

/**
 * Base exception for payment gateway errors
 */
class PaymentException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        public readonly ?array $context = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get exception context data
     */
    public function getContext(): ?array
    {
        return $this->context;
    }
}
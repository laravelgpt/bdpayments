<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Exceptions;

/**
 * Exception thrown when payment data validation fails
 */
class ValidationException extends PaymentException
{
    public function __construct(string $message = 'Payment data validation failed', array $context = [])
    {
        parent::__construct($message, 0, null, $context);
    }
}
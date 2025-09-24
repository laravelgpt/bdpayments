<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Events;

use BDPayments\LaravelPaymentGateway\Models\Payment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class PaymentEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Payment $payment,
        public readonly array $data = []
    ) {}

    /**
     * Get the payment instance.
     */
    public function getPayment(): Payment
    {
        return $this->payment;
    }

    /**
     * Get the event data.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get the payment gateway.
     */
    public function getGateway(): string
    {
        return $this->payment->gateway;
    }

    /**
     * Get the payment amount.
     */
    public function getAmount(): float
    {
        return $this->payment->amount;
    }

    /**
     * Get the payment currency.
     */
    public function getCurrency(): string
    {
        return $this->payment->currency;
    }

    /**
     * Get the payment status.
     */
    public function getStatus(): string
    {
        return $this->payment->status;
    }
}

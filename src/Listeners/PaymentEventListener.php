<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Listeners;

use BDPayments\LaravelPaymentGateway\Events\PaymentEvent;
use BDPayments\LaravelPaymentGateway\Events\PaymentInitialized;
use BDPayments\LaravelPaymentGateway\Events\PaymentCompleted;
use BDPayments\LaravelPaymentGateway\Events\PaymentFailed;
use BDPayments\LaravelPaymentGateway\Events\PaymentRefunded;
use BDPayments\LaravelPaymentGateway\Services\PaymentLogger;
use BDPayments\LaravelPaymentGateway\Services\AIAgentService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class PaymentEventListener
{
    public function __construct(
        private readonly PaymentLogger $logger,
        private readonly AIAgentService $aiAgent
    ) {}

    /**
     * Handle payment initialized event.
     */
    public function handlePaymentInitialized(PaymentInitialized $event): void
    {
        $this->logger->log('payment_initialized', $event->getPayment()->transaction_id, [
            'gateway' => $event->getGateway(),
            'amount' => $event->getAmount(),
            'currency' => $event->getCurrency(),
        ]);

        // AI analysis for new payments
        if (config('payment-gateway.ai_agent.enabled', true)) {
            $this->aiAgent->analyzePaymentPatterns($event->getPayment());
        }

        Log::info('Payment initialized', [
            'payment_id' => $event->getPayment()->id,
            'gateway' => $event->getGateway(),
            'amount' => $event->getAmount(),
        ]);
    }

    /**
     * Handle payment completed event.
     */
    public function handlePaymentCompleted(PaymentCompleted $event): void
    {
        $this->logger->log('payment_completed', $event->getPayment()->transaction_id, [
            'gateway' => $event->getGateway(),
            'amount' => $event->getAmount(),
            'currency' => $event->getCurrency(),
        ]);

        // Generate notifications for successful payments
        if (config('payment-gateway.ai_agent.notifications_enabled', true)) {
            $analysis = $this->aiAgent->analyzePaymentPatterns($event->getPayment());
            $this->aiAgent->generateNotifications($event->getPayment(), $analysis);
        }

        Log::info('Payment completed', [
            'payment_id' => $event->getPayment()->id,
            'gateway' => $event->getGateway(),
            'amount' => $event->getAmount(),
        ]);
    }

    /**
     * Handle payment failed event.
     */
    public function handlePaymentFailed(PaymentFailed $event): void
    {
        $this->logger->log('payment_failed', $event->getPayment()->transaction_id, [
            'gateway' => $event->getGateway(),
            'amount' => $event->getAmount(),
            'currency' => $event->getCurrency(),
            'reason' => $event->getData()['reason'] ?? 'Unknown',
        ]);

        // AI analysis for failed payments
        if (config('payment-gateway.ai_agent.enabled', true)) {
            $this->aiAgent->analyzePaymentPatterns($event->getPayment());
        }

        Log::warning('Payment failed', [
            'payment_id' => $event->getPayment()->id,
            'gateway' => $event->getGateway(),
            'amount' => $event->getAmount(),
            'reason' => $event->getData()['reason'] ?? 'Unknown',
        ]);
    }

    /**
     * Handle payment refunded event.
     */
    public function handlePaymentRefunded(PaymentRefunded $event): void
    {
        $this->logger->log('payment_refunded', $event->getPayment()->transaction_id, [
            'gateway' => $event->getGateway(),
            'amount' => $event->getAmount(),
            'currency' => $event->getCurrency(),
            'refund_amount' => $event->getData()['refund_amount'] ?? $event->getAmount(),
        ]);

        Log::info('Payment refunded', [
            'payment_id' => $event->getPayment()->id,
            'gateway' => $event->getGateway(),
            'amount' => $event->getAmount(),
            'refund_amount' => $event->getData()['refund_amount'] ?? $event->getAmount(),
        ]);
    }

    /**
     * Handle any payment event.
     */
    public function handlePaymentEvent(PaymentEvent $event): void
    {
        $this->logger->log('payment_event', $event->getPayment()->transaction_id, [
            'event_type' => get_class($event),
            'gateway' => $event->getGateway(),
            'amount' => $event->getAmount(),
            'currency' => $event->getCurrency(),
            'status' => $event->getStatus(),
        ]);
    }
}

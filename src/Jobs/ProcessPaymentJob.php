<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Jobs;

use BDPayments\LaravelPaymentGateway\Models\Payment;
use BDPayments\LaravelPaymentGateway\Services\PaymentGatewayService;
use BDPayments\LaravelPaymentGateway\Services\AIAgentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly Payment $payment,
        public readonly array $data = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        PaymentGatewayService $paymentService,
        AIAgentService $aiAgent
    ): void {
        try {
            Log::info('Processing payment job', [
                'payment_id' => $this->payment->id,
                'gateway' => $this->payment->gateway,
            ]);

            // Process payment based on current status
            switch ($this->payment->status) {
                case 'pending':
                    $this->processPendingPayment($paymentService);
                    break;
                case 'processing':
                    $this->processProcessingPayment($paymentService);
                    break;
                default:
                    Log::info('Payment already processed', [
                        'payment_id' => $this->payment->id,
                        'status' => $this->payment->status,
                    ]);
            }

            // AI analysis after processing
            if (config('payment-gateway.ai_agent.enabled', true)) {
                $aiAgent->analyzePaymentPatterns($this->payment);
            }

        } catch (\Exception $e) {
            Log::error('Payment job failed', [
                'payment_id' => $this->payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->fail($e);
        }
    }

    /**
     * Process pending payment.
     */
    private function processPendingPayment(PaymentGatewayService $paymentService): void
    {
        // Verify payment status
        $result = $paymentService->verifyPayment(
            $this->payment->gateway,
            $this->payment->transaction_id
        );

        if ($result->success) {
            $this->payment->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Dispatch payment completed event
            \BDPayments\LaravelPaymentGateway\Events\PaymentCompleted::dispatch(
                $this->payment,
                $this->data
            );
        } else {
            $this->payment->update([
                'status' => 'failed',
                'failed_at' => now(),
            ]);

            // Dispatch payment failed event
            \BDPayments\LaravelPaymentGateway\Events\PaymentFailed::dispatch(
                $this->payment,
                array_merge($this->data, ['reason' => $result->message])
            );
        }
    }

    /**
     * Process processing payment.
     */
    private function processProcessingPayment(PaymentGatewayService $paymentService): void
    {
        // Check payment status
        $result = $paymentService->getPaymentStatus(
            $this->payment->gateway,
            $this->payment->transaction_id
        );

        if ($result->success) {
            $this->payment->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Dispatch payment completed event
            \BDPayments\LaravelPaymentGateway\Events\PaymentCompleted::dispatch(
                $this->payment,
                $this->data
            );
        } else {
            $this->payment->update([
                'status' => 'failed',
                'failed_at' => now(),
            ]);

            // Dispatch payment failed event
            \BDPayments\LaravelPaymentGateway\Events\PaymentFailed::dispatch(
                $this->payment,
                array_merge($this->data, ['reason' => $result->message])
            );
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Payment job permanently failed', [
            'payment_id' => $this->payment->id,
            'error' => $exception->getMessage(),
        ]);

        // Update payment status to failed
        $this->payment->update([
            'status' => 'failed',
            'failed_at' => now(),
        ]);

        // Dispatch payment failed event
        \BDPayments\LaravelPaymentGateway\Events\PaymentFailed::dispatch(
            $this->payment,
            array_merge($this->data, ['reason' => 'Job processing failed'])
        );
    }
}

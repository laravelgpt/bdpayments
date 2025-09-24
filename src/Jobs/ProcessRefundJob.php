<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Jobs;

use BDPayments\LaravelPaymentGateway\Models\Payment;
use BDPayments\LaravelPaymentGateway\Services\PaymentGatewayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessRefundJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly Payment $payment,
        public readonly float $amount,
        public readonly string $reason = ''
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PaymentGatewayService $paymentService): void
    {
        try {
            Log::info('Processing refund job', [
                'payment_id' => $this->payment->id,
                'gateway' => $this->payment->gateway,
                'amount' => $this->amount,
            ]);

            // Process refund
            $result = $paymentService->refundPayment(
                $this->payment->gateway,
                $this->payment->transaction_id,
                $this->amount,
                $this->reason
            );

            if ($result->success) {
                // Update payment status
                $this->payment->update([
                    'status' => 'refunded',
                    'refunded_at' => now(),
                ]);

                // Create refund record
                $this->payment->refunds()->create([
                    'refund_id' => $result->refundId ?? null,
                    'amount' => $this->amount,
                    'reason' => $this->reason,
                    'status' => 'completed',
                    'gateway_response' => $result->gatewayResponse,
                ]);

                // Dispatch payment refunded event
                \BDPayments\LaravelPaymentGateway\Events\PaymentRefunded::dispatch(
                    $this->payment,
                    [
                        'refund_amount' => $this->amount,
                        'reason' => $this->reason,
                    ]
                );

                Log::info('Refund processed successfully', [
                    'payment_id' => $this->payment->id,
                    'refund_amount' => $this->amount,
                ]);
            } else {
                throw new \Exception($result->message ?? 'Refund failed');
            }

        } catch (\Exception $e) {
            Log::error('Refund job failed', [
                'payment_id' => $this->payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->fail($e);
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Refund job permanently failed', [
            'payment_id' => $this->payment->id,
            'error' => $exception->getMessage(),
        ]);

        // Create failed refund record
        $this->payment->refunds()->create([
            'amount' => $this->amount,
            'reason' => $this->reason,
            'status' => 'failed',
            'gateway_response' => ['error' => $exception->getMessage()],
        ]);
    }
}

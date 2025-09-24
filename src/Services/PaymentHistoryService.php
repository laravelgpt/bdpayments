<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Services;

use BDPayments\LaravelPaymentGateway\Models\Payment;
use BDPayments\LaravelPaymentGateway\Models\PaymentHistory;
use BDPayments\LaravelPaymentGateway\Models\PaymentProblem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PaymentHistoryService
{
    /**
     * Log payment action
     */
    public function logAction(
        Payment $payment,
        string $action,
        ?string $statusFrom = null,
        ?string $statusTo = null,
        ?array $gatewayResponse = null,
        ?string $notes = null,
        ?string $adminNotes = null
    ): PaymentHistory {
        $history = PaymentHistory::create([
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
            'action' => $action,
            'status_from' => $statusFrom,
            'status_to' => $statusTo,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'gateway_response' => $gatewayResponse,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'notes' => $notes,
            'admin_notes' => $adminNotes,
        ]);

        Log::info("Payment action logged: {$action}", [
            'payment_id' => $payment->id,
            'action' => $action,
            'status_from' => $statusFrom,
            'status_to' => $statusTo,
        ]);

        return $history;
    }

    /**
     * Log payment creation
     */
    public function logPaymentCreated(Payment $payment): PaymentHistory
    {
        return $this->logAction(
            $payment,
            PaymentHistory::ACTION_CREATED,
            null,
            $payment->status,
            null,
            'Payment created'
        );
    }

    /**
     * Log payment initialization
     */
    public function logPaymentInitialized(Payment $payment, array $gatewayResponse = []): PaymentHistory
    {
        return $this->logAction(
            $payment,
            PaymentHistory::ACTION_INITIALIZED,
            $payment->status,
            'processing',
            $gatewayResponse,
            'Payment initialized with gateway'
        );
    }

    /**
     * Log payment completion
     */
    public function logPaymentCompleted(Payment $payment, array $gatewayResponse = []): PaymentHistory
    {
        return $this->logAction(
            $payment,
            PaymentHistory::ACTION_COMPLETED,
            $payment->status,
            Payment::STATUS_COMPLETED,
            $gatewayResponse,
            'Payment completed successfully'
        );
    }

    /**
     * Log payment failure
     */
    public function logPaymentFailed(Payment $payment, array $gatewayResponse = [], string $reason = ''): PaymentHistory
    {
        return $this->logAction(
            $payment,
            PaymentHistory::ACTION_FAILED,
            $payment->status,
            Payment::STATUS_FAILED,
            $gatewayResponse,
            'Payment failed: ' . $reason
        );
    }

    /**
     * Log payment refund
     */
    public function logPaymentRefunded(Payment $payment, float $amount, string $reason = ''): PaymentHistory
    {
        return $this->logAction(
            $payment,
            PaymentHistory::ACTION_REFUNDED,
            $payment->status,
            Payment::STATUS_REFUNDED,
            ['refund_amount' => $amount, 'reason' => $reason],
            'Payment refunded: ' . $reason
        );
    }

    /**
     * Log webhook received
     */
    public function logWebhookReceived(Payment $payment, array $webhookData): PaymentHistory
    {
        return $this->logAction(
            $payment,
            PaymentHistory::ACTION_WEBHOOK_RECEIVED,
            null,
            null,
            $webhookData,
            'Webhook received from gateway'
        );
    }

    /**
     * Log callback received
     */
    public function logCallbackReceived(Payment $payment, array $callbackData): PaymentHistory
    {
        return $this->logAction(
            $payment,
            PaymentHistory::ACTION_CALLBACK_RECEIVED,
            null,
            null,
            $callbackData,
            'Callback received from gateway'
        );
    }

    /**
     * Log admin update
     */
    public function logAdminUpdate(Payment $payment, string $adminNotes): PaymentHistory
    {
        return $this->logAction(
            $payment,
            PaymentHistory::ACTION_ADMIN_UPDATED,
            null,
            null,
            null,
            'Admin updated payment',
            $adminNotes
        );
    }

    /**
     * Report payment problem
     */
    public function reportProblem(
        Payment $payment,
        string $problemType,
        string $title,
        string $description,
        string $severity = PaymentProblem::SEVERITY_MEDIUM,
        string $priority = PaymentProblem::PRIORITY_NORMAL,
        array $tags = []
    ): PaymentProblem {
        $problem = PaymentProblem::create([
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
            'problem_type' => $problemType,
            'severity' => $severity,
            'title' => $title,
            'description' => $description,
            'status' => PaymentProblem::STATUS_OPEN,
            'priority' => $priority,
            'reported_by' => Auth::id(),
            'tags' => $tags,
        ]);

        // Log the problem report in history
        $this->logAction(
            $payment,
            PaymentHistory::ACTION_PROBLEM_REPORTED,
            null,
            null,
            null,
            "Problem reported: {$title}",
            "Problem ID: {$problem->id}"
        );

        Log::warning('Payment problem reported', [
            'payment_id' => $payment->id,
            'problem_id' => $problem->id,
            'problem_type' => $problemType,
            'severity' => $severity,
        ]);

        return $problem;
    }

    /**
     * Resolve payment problem
     */
    public function resolveProblem(
        PaymentProblem $problem,
        string $resolutionNotes = ''
    ): void {
        $problem->markAsResolved(Auth::id(), $resolutionNotes);

        // Log the resolution in history
        $this->logAction(
            $problem->payment,
            PaymentHistory::ACTION_PROBLEM_RESOLVED,
            null,
            null,
            null,
            "Problem resolved: {$problem->title}",
            "Resolution: {$resolutionNotes}"
        );

        Log::info('Payment problem resolved', [
            'payment_id' => $problem->payment_id,
            'problem_id' => $problem->id,
            'resolved_by' => Auth::id(),
        ]);
    }

    /**
     * Get payment history
     */
    public function getPaymentHistory(Payment $payment): \Illuminate\Database\Eloquent\Collection
    {
        return $payment->histories()->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get payment problems
     */
    public function getPaymentProblems(Payment $payment): \Illuminate\Database\Eloquent\Collection
    {
        return $payment->problems()->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get payment statistics
     */
    public function getPaymentStatistics(array $filters = []): array
    {
        $query = Payment::query();

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['gateway'])) {
            $query->where('gateway', $filters['gateway']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return [
            'total_payments' => $query->count(),
            'total_amount' => $query->sum('amount'),
            'completed_payments' => $query->where('status', Payment::STATUS_COMPLETED)->count(),
            'failed_payments' => $query->where('status', Payment::STATUS_FAILED)->count(),
            'refunded_payments' => $query->whereIn('status', [Payment::STATUS_REFUNDED, Payment::STATUS_PARTIALLY_REFUNDED])->count(),
            'pending_payments' => $query->where('status', Payment::STATUS_PENDING)->count(),
        ];
    }

    /**
     * Get problem statistics
     */
    public function getProblemStatistics(array $filters = []): array
    {
        $query = PaymentProblem::query();

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return [
            'total_problems' => $query->count(),
            'open_problems' => $query->whereIn('status', [
                PaymentProblem::STATUS_OPEN,
                PaymentProblem::STATUS_IN_PROGRESS,
                PaymentProblem::STATUS_PENDING_CUSTOMER,
                PaymentProblem::STATUS_PENDING_GATEWAY
            ])->count(),
            'resolved_problems' => $query->whereIn('status', [
                PaymentProblem::STATUS_RESOLVED,
                PaymentProblem::STATUS_CLOSED
            ])->count(),
            'critical_problems' => $query->where('severity', PaymentProblem::SEVERITY_CRITICAL)->count(),
            'urgent_problems' => $query->where('priority', PaymentProblem::PRIORITY_URGENT)->count(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'user_id',
        'action',
        'status_from',
        'status_to',
        'amount',
        'currency',
        'gateway_response',
        'ip_address',
        'user_agent',
        'notes',
        'admin_notes',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'resolved_at' => 'datetime',
    ];

    /**
     * Payment history actions
     */
    public const ACTION_CREATED = 'created';
    public const ACTION_INITIALIZED = 'initialized';
    public const ACTION_PROCESSING = 'processing';
    public const ACTION_COMPLETED = 'completed';
    public const ACTION_FAILED = 'failed';
    public const ACTION_CANCELLED = 'cancelled';
    public const ACTION_REFUNDED = 'refunded';
    public const ACTION_PARTIALLY_REFUNDED = 'partially_refunded';
    public const ACTION_VERIFIED = 'verified';
    public const ACTION_WEBHOOK_RECEIVED = 'webhook_received';
    public const ACTION_CALLBACK_RECEIVED = 'callback_received';
    public const ACTION_ADMIN_UPDATED = 'admin_updated';
    public const ACTION_PROBLEM_REPORTED = 'problem_reported';
    public const ACTION_PROBLEM_RESOLVED = 'problem_resolved';

    /**
     * Get the payment that owns the history
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the user that owns the history
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'));
    }

    /**
     * Get the admin who resolved the issue
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'), 'resolved_by');
    }

    /**
     * Get payment problems for this history
     */
    public function problems(): HasMany
    {
        return $this->hasMany(PaymentProblem::class);
    }

    /**
     * Scope a query to only include specific action
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to only include resolved issues
     */
    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }

    /**
     * Scope a query to only include unresolved issues
     */
    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    /**
     * Scope a query to only include problem reports
     */
    public function scopeProblems($query)
    {
        return $query->where('action', self::ACTION_PROBLEM_REPORTED);
    }

    /**
     * Check if this is a problem report
     */
    public function isProblemReport(): bool
    {
        return $this->action === self::ACTION_PROBLEM_REPORTED;
    }

    /**
     * Check if this is resolved
     */
    public function isResolved(): bool
    {
        return !is_null($this->resolved_at);
    }

    /**
     * Mark as resolved
     */
    public function markAsResolved(int $resolvedBy, string $adminNotes = ''): void
    {
        $this->update([
            'resolved_at' => now(),
            'resolved_by' => $resolvedBy,
            'admin_notes' => $adminNotes,
        ]);
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmount(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    /**
     * Get status change description
     */
    public function getStatusChangeDescription(): string
    {
        if ($this->status_from && $this->status_to) {
            return "Status changed from {$this->status_from} to {$this->status_to}";
        }
        
        return ucfirst(str_replace('_', ' ', $this->action));
    }

    /**
     * Get action badge class
     */
    public function getActionBadgeClass(): string
    {
        return match ($this->action) {
            self::ACTION_CREATED => 'badge-info',
            self::ACTION_INITIALIZED => 'badge-primary',
            self::ACTION_PROCESSING => 'badge-warning',
            self::ACTION_COMPLETED => 'badge-success',
            self::ACTION_FAILED => 'badge-danger',
            self::ACTION_CANCELLED => 'badge-secondary',
            self::ACTION_REFUNDED, self::ACTION_PARTIALLY_REFUNDED => 'badge-warning',
            self::ACTION_VERIFIED => 'badge-success',
            self::ACTION_WEBHOOK_RECEIVED, self::ACTION_CALLBACK_RECEIVED => 'badge-info',
            self::ACTION_ADMIN_UPDATED => 'badge-primary',
            self::ACTION_PROBLEM_REPORTED => 'badge-danger',
            self::ACTION_PROBLEM_RESOLVED => 'badge-success',
            default => 'badge-secondary',
        };
    }
}

<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentProblem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'payment_history_id',
        'user_id',
        'problem_type',
        'severity',
        'title',
        'description',
        'status',
        'priority',
        'assigned_to',
        'reported_by',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
        'attachments',
        'tags',
    ];

    protected $casts = [
        'attachments' => 'array',
        'tags' => 'array',
        'resolved_at' => 'datetime',
    ];

    /**
     * Problem types
     */
    public const TYPE_PAYMENT_FAILED = 'payment_failed';
    public const TYPE_REFUND_ISSUE = 'refund_issue';
    public const TYPE_VERIFICATION_FAILED = 'verification_failed';
    public const TYPE_WEBHOOK_FAILED = 'webhook_failed';
    public const TYPE_CALLBACK_FAILED = 'callback_failed';
    public const TYPE_AMOUNT_MISMATCH = 'amount_mismatch';
    public const TYPE_DUPLICATE_PAYMENT = 'duplicate_payment';
    public const TYPE_GATEWAY_ERROR = 'gateway_error';
    public const TYPE_NETWORK_ERROR = 'network_error';
    public const TYPE_TIMEOUT = 'timeout';
    public const TYPE_FRAUD_SUSPECTED = 'fraud_suspected';
    public const TYPE_CUSTOMER_COMPLAINT = 'customer_complaint';
    public const TYPE_TECHNICAL_ISSUE = 'technical_issue';

    /**
     * Problem severity levels
     */
    public const SEVERITY_LOW = 'low';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_CRITICAL = 'critical';

    /**
     * Problem status
     */
    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_PENDING_CUSTOMER = 'pending_customer';
    public const STATUS_PENDING_GATEWAY = 'pending_gateway';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_DUPLICATE = 'duplicate';

    /**
     * Problem priority
     */
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    /**
     * Get the payment that owns the problem
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the payment history that owns the problem
     */
    public function paymentHistory(): BelongsTo
    {
        return $this->belongsTo(PaymentHistory::class);
    }

    /**
     * Get the user that owns the problem
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'));
    }

    /**
     * Get the admin assigned to the problem
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'), 'assigned_to');
    }

    /**
     * Get the user who reported the problem
     */
    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'), 'reported_by');
    }

    /**
     * Get the admin who resolved the problem
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'), 'resolved_by');
    }

    /**
     * Get problem comments
     */
    public function comments(): HasMany
    {
        return $this->hasMany(PaymentProblemComment::class);
    }

    /**
     * Scope a query to only include specific problem type
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('problem_type', $type);
    }

    /**
     * Scope a query to only include specific severity
     */
    public function scopeForSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope a query to only include specific status
     */
    public function scopeForStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include open problems
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_PENDING_CUSTOMER, self::STATUS_PENDING_GATEWAY]);
    }

    /**
     * Scope a query to only include resolved problems
     */
    public function scopeResolved($query)
    {
        return $query->whereIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    /**
     * Scope a query to only include critical problems
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }

    /**
     * Scope a query to only include urgent problems
     */
    public function scopeUrgent($query)
    {
        return $query->where('priority', self::PRIORITY_URGENT);
    }

    /**
     * Check if problem is open
     */
    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_PENDING_CUSTOMER, self::STATUS_PENDING_GATEWAY]);
    }

    /**
     * Check if problem is resolved
     */
    public function isResolved(): bool
    {
        return in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    /**
     * Check if problem is critical
     */
    public function isCritical(): bool
    {
        return $this->severity === self::SEVERITY_CRITICAL;
    }

    /**
     * Check if problem is urgent
     */
    public function isUrgent(): bool
    {
        return $this->priority === self::PRIORITY_URGENT;
    }

    /**
     * Assign problem to admin
     */
    public function assignTo(int $adminId): void
    {
        $this->update([
            'assigned_to' => $adminId,
            'status' => self::STATUS_IN_PROGRESS,
        ]);
    }

    /**
     * Mark as resolved
     */
    public function markAsResolved(int $resolvedBy, string $resolutionNotes = ''): void
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolved_by' => $resolvedBy,
            'resolution_notes' => $resolutionNotes,
        ]);
    }

    /**
     * Close problem
     */
    public function close(int $closedBy, string $resolutionNotes = ''): void
    {
        $this->update([
            'status' => self::STATUS_CLOSED,
            'resolved_at' => now(),
            'resolved_by' => $closedBy,
            'resolution_notes' => $resolutionNotes,
        ]);
    }

    /**
     * Get severity badge class
     */
    public function getSeverityBadgeClass(): string
    {
        return match ($this->severity) {
            self::SEVERITY_LOW => 'badge-info',
            self::SEVERITY_MEDIUM => 'badge-warning',
            self::SEVERITY_HIGH => 'badge-danger',
            self::SEVERITY_CRITICAL => 'badge-dark',
            default => 'badge-secondary',
        };
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'badge-primary',
            self::STATUS_IN_PROGRESS => 'badge-warning',
            self::STATUS_PENDING_CUSTOMER => 'badge-info',
            self::STATUS_PENDING_GATEWAY => 'badge-warning',
            self::STATUS_RESOLVED => 'badge-success',
            self::STATUS_CLOSED => 'badge-secondary',
            self::STATUS_DUPLICATE => 'badge-secondary',
            default => 'badge-secondary',
        };
    }

    /**
     * Get priority badge class
     */
    public function getPriorityBadgeClass(): string
    {
        return match ($this->priority) {
            self::PRIORITY_LOW => 'badge-info',
            self::PRIORITY_NORMAL => 'badge-primary',
            self::PRIORITY_HIGH => 'badge-warning',
            self::PRIORITY_URGENT => 'badge-danger',
            default => 'badge-secondary',
        };
    }
}

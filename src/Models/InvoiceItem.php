<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
        'discount_rate',
        'line_total',
        'tax_amount',
        'discount_amount',
        'final_amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'line_total' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
    ];

    /**
     * Get the invoice that owns the item
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Calculate line total
     */
    public function calculateLineTotal(): float
    {
        return $this->quantity * $this->unit_price;
    }

    /**
     * Calculate tax amount
     */
    public function calculateTaxAmount(): float
    {
        return $this->calculateLineTotal() * ($this->tax_rate / 100);
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscountAmount(): float
    {
        return $this->calculateLineTotal() * ($this->discount_rate / 100);
    }

    /**
     * Calculate final amount
     */
    public function calculateFinalAmount(): float
    {
        return $this->calculateLineTotal() + $this->calculateTaxAmount() - $this->calculateDiscountAmount();
    }

    /**
     * Update calculated amounts
     */
    public function updateCalculatedAmounts(): void
    {
        $this->update([
            'line_total' => $this->calculateLineTotal(),
            'tax_amount' => $this->calculateTaxAmount(),
            'discount_amount' => $this->calculateDiscountAmount(),
            'final_amount' => $this->calculateFinalAmount(),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Services;

use BDPayments\LaravelPaymentGateway\Models\Invoice;
use BDPayments\LaravelPaymentGateway\Models\InvoiceItem;
use BDPayments\LaravelPaymentGateway\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    /**
     * Generate invoice for payment
     */
    public function generateInvoice(Payment $payment, array $invoiceData = []): Invoice
    {
        try {
            DB::beginTransaction();

            $invoice = Invoice::create([
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'invoice_date' => $invoiceData['invoice_date'] ?? now()->toDateString(),
                'due_date' => $invoiceData['due_date'] ?? now()->addDays(30)->toDateString(),
                'status' => Invoice::STATUS_DRAFT,
                'subtotal' => $payment->amount,
                'tax_amount' => $invoiceData['tax_amount'] ?? 0,
                'discount_amount' => $invoiceData['discount_amount'] ?? 0,
                'total_amount' => $payment->amount,
                'currency' => $payment->currency,
                'billing_address' => $invoiceData['billing_address'] ?? null,
                'shipping_address' => $invoiceData['shipping_address'] ?? null,
                'notes' => $invoiceData['notes'] ?? null,
                'terms_conditions' => $invoiceData['terms_conditions'] ?? null,
            ]);

            // Add invoice items if provided
            if (isset($invoiceData['items']) && is_array($invoiceData['items'])) {
                foreach ($invoiceData['items'] as $item) {
                    $this->addInvoiceItem($invoice, $item);
                }
            } else {
                // Add default item based on payment
                $this->addInvoiceItem($invoice, [
                    'description' => 'Payment for Order #' . $payment->order_id,
                    'quantity' => 1,
                    'unit_price' => $payment->amount,
                    'tax_rate' => 0,
                    'discount_rate' => 0,
                ]);
            }

            // Update invoice totals
            $this->updateInvoiceTotals($invoice);

            DB::commit();

            Log::info('Invoice generated successfully', [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'invoice_number' => $invoice->invoice_number,
            ]);

            return $invoice;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to generate invoice', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Add item to invoice
     */
    public function addInvoiceItem(Invoice $invoice, array $itemData): InvoiceItem
    {
        $item = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => $itemData['description'],
            'quantity' => $itemData['quantity'],
            'unit_price' => $itemData['unit_price'],
            'tax_rate' => $itemData['tax_rate'] ?? 0,
            'discount_rate' => $itemData['discount_rate'] ?? 0,
        ]);

        // Calculate amounts
        $item->updateCalculatedAmounts();

        return $item;
    }

    /**
     * Update invoice totals
     */
    public function updateInvoiceTotals(Invoice $invoice): void
    {
        $subtotal = $invoice->items()->sum('line_total');
        $taxAmount = $invoice->items()->sum('tax_amount');
        $discountAmount = $invoice->items()->sum('discount_amount');
        $totalAmount = $subtotal + $taxAmount - $discountAmount;

        $invoice->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
        ]);
    }

    /**
     * Send invoice
     */
    public function sendInvoice(Invoice $invoice): void
    {
        $invoice->markAsSent();
        
        Log::info('Invoice sent', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
        ]);
    }

    /**
     * Mark invoice as paid
     */
    public function markInvoiceAsPaid(Invoice $invoice): void
    {
        $invoice->markAsPaid();
        
        Log::info('Invoice marked as paid', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
        ]);
    }

    /**
     * Cancel invoice
     */
    public function cancelInvoice(Invoice $invoice, string $reason = ''): void
    {
        $invoice->cancel();
        
        Log::info('Invoice cancelled', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'reason' => $reason,
        ]);
    }

    /**
     * Get invoice PDF
     */
    public function generateInvoicePdf(Invoice $invoice): string
    {
        // This would integrate with a PDF generation library like dompdf or tcpdf
        // For now, return a placeholder
        return "PDF content for invoice {$invoice->invoice_number}";
    }

    /**
     * Get invoice statistics
     */
    public function getInvoiceStatistics(array $filters = []): array
    {
        $query = Invoice::query();

        if (isset($filters['date_from'])) {
            $query->where('invoice_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('invoice_date', '<=', $filters['date_to']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return [
            'total_invoices' => $query->count(),
            'total_amount' => $query->sum('total_amount'),
            'paid_invoices' => $query->where('status', Invoice::STATUS_PAID)->count(),
            'paid_amount' => $query->where('status', Invoice::STATUS_PAID)->sum('total_amount'),
            'overdue_invoices' => $query->where('status', Invoice::STATUS_OVERDUE)->count(),
            'overdue_amount' => $query->where('status', Invoice::STATUS_OVERDUE)->sum('total_amount'),
        ];
    }
}

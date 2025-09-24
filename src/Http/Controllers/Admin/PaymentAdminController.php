<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use BDPayments\LaravelPaymentGateway\Http\Controllers\Controller;
use BDPayments\LaravelPaymentGateway\Models\Payment;
use BDPayments\LaravelPaymentGateway\Models\PaymentProblem;
use BDPayments\LaravelPaymentGateway\Models\Invoice;
use BDPayments\LaravelPaymentGateway\Services\PaymentHistoryService;
use BDPayments\LaravelPaymentGateway\Services\InvoiceService;
use Illuminate\Support\Facades\Validator;

class PaymentAdminController extends Controller
{
    public function __construct(
        private readonly PaymentHistoryService $historyService,
        private readonly InvoiceService $invoiceService
    ) {}

    /**
     * Display payment dashboard
     */
    public function dashboard(): View
    {
        $paymentStats = $this->historyService->getPaymentStatistics();
        $problemStats = $this->historyService->getProblemStatistics();
        $invoiceStats = $this->invoiceService->getInvoiceStatistics();

        return view('payment-gateway::admin.dashboard', [
            'paymentStats' => $paymentStats,
            'problemStats' => $problemStats,
            'invoiceStats' => $invoiceStats,
        ]);
    }

    /**
     * Display payments list
     */
    public function index(Request $request): View
    {
        $query = Payment::with(['user', 'histories', 'problems']);

        // Apply filters
        if ($request->filled('gateway')) {
            $query->where('gateway', $request->gateway);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                  ->orWhere('payment_id', 'like', "%{$search}%")
                  ->orWhere('transaction_id', 'like', "%{$search}%");
            });
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('payment-gateway::admin.payments.index', [
            'payments' => $payments,
            'filters' => $request->only(['gateway', 'status', 'date_from', 'date_to', 'search']),
        ]);
    }

    /**
     * Display payment details
     */
    public function show(Payment $payment): View
    {
        $payment->load(['user', 'histories', 'problems', 'refunds']);
        
        return view('payment-gateway::admin.payments.show', [
            'payment' => $payment,
        ]);
    }

    /**
     * Update payment status
     */
    public function updateStatus(Request $request, Payment $payment): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,processing,completed,failed,cancelled,refunded,partially_refunded',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $oldStatus = $payment->status;
        $newStatus = $request->status;

        $payment->update(['status' => $newStatus]);

        // Log the status change
        $this->historyService->logAction(
            $payment,
            'admin_status_update',
            $oldStatus,
            $newStatus,
            null,
            'Status updated by admin',
            $request->admin_notes
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment status updated successfully',
        ]);
    }

    /**
     * Display payment problems
     */
    public function problems(Request $request): View
    {
        $query = PaymentProblem::with(['payment', 'user', 'assignedTo', 'reportedBy']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $problems = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('payment-gateway::admin.problems.index', [
            'problems' => $problems,
            'filters' => $request->only(['status', 'severity', 'priority', 'assigned_to', 'search']),
        ]);
    }

    /**
     * Display problem details
     */
    public function showProblem(PaymentProblem $problem): View
    {
        $problem->load(['payment', 'user', 'assignedTo', 'reportedBy', 'comments.user']);
        
        return view('payment-gateway::admin.problems.show', [
            'problem' => $problem,
        ]);
    }

    /**
     * Assign problem to admin
     */
    public function assignProblem(Request $request, PaymentProblem $problem): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'assigned_to' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $problem->assignTo($request->assigned_to);

        return response()->json([
            'success' => true,
            'message' => 'Problem assigned successfully',
        ]);
    }

    /**
     * Resolve problem
     */
    public function resolveProblem(Request $request, PaymentProblem $problem): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'resolution_notes' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $this->historyService->resolveProblem($problem, $request->resolution_notes);

        return response()->json([
            'success' => true,
            'message' => 'Problem resolved successfully',
        ]);
    }

    /**
     * Display invoices
     */
    public function invoices(Request $request): View
    {
        $query = Invoice::with(['payment', 'user', 'items']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('payment', function ($q) use ($search) {
                      $q->where('order_id', 'like', "%{$search}%");
                  });
            });
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('payment-gateway::admin.invoices.index', [
            'invoices' => $invoices,
            'filters' => $request->only(['status', 'date_from', 'date_to', 'search']),
        ]);
    }

    /**
     * Display invoice details
     */
    public function showInvoice(Invoice $invoice): View
    {
        $invoice->load(['payment', 'user', 'items']);
        
        return view('payment-gateway::admin.invoices.show', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * Generate invoice PDF
     */
    public function downloadInvoice(Invoice $invoice)
    {
        $pdfContent = $this->invoiceService->generateInvoicePdf($invoice);
        
        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="invoice-' . $invoice->invoice_number . '.pdf"');
    }

    /**
     * Send invoice
     */
    public function sendInvoice(Invoice $invoice): JsonResponse
    {
        $this->invoiceService->sendInvoice($invoice);

        return response()->json([
            'success' => true,
            'message' => 'Invoice sent successfully',
        ]);
    }

    /**
     * Export payments data
     */
    public function exportPayments(Request $request)
    {
        $query = Payment::with(['user']);

        // Apply same filters as index
        if ($request->filled('gateway')) {
            $query->where('gateway', $request->gateway);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $payments = $query->orderBy('created_at', 'desc')->get();

        // Generate CSV
        $csv = "ID,Order ID,Gateway,Amount,Currency,Status,User,Created At\n";
        
        foreach ($payments as $payment) {
            $csv .= implode(',', [
                $payment->id,
                $payment->order_id,
                $payment->gateway,
                $payment->amount,
                $payment->currency,
                $payment->status,
                $payment->user->name ?? 'N/A',
                $payment->created_at->format('Y-m-d H:i:s'),
            ]) . "\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="payments-' . date('Y-m-d') . '.csv"');
    }
}

<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Http\Controllers;

use BDPayments\LaravelPaymentGateway\Services\TransactionReportService;
use BDPayments\LaravelPaymentGateway\Services\QRCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    public function __construct(
        private readonly TransactionReportService $reportService,
        private readonly QRCodeService $qrCodeService
    ) {}

    /**
     * Get transaction report
     */
    public function getTransactionReport(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'date_from', 'date_to', 'gateway', 'status', 'currency',
                'amount_min', 'amount_max', 'user_id'
            ]);

            $report = $this->reportService->generateTransactionReport($filters);

            return response()->json([
                'success' => true,
                'data' => $report,
                'filters' => $filters,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate transaction report', [
                'error' => $e->getMessage(),
                'filters' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate transaction report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get gateway performance report
     */
    public function getGatewayPerformanceReport(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'date_from', 'date_to', 'gateway', 'status', 'currency'
            ]);

            $report = $this->reportService->generateGatewayPerformanceReport($filters);

            return response()->json([
                'success' => true,
                'data' => $report,
                'filters' => $filters,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate gateway performance report', [
                'error' => $e->getMessage(),
                'filters' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate gateway performance report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get financial report
     */
    public function getFinancialReport(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'date_from', 'date_to', 'gateway', 'currency'
            ]);

            $report = $this->reportService->generateFinancialReport($filters);

            return response()->json([
                'success' => true,
                'data' => $report,
                'filters' => $filters,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate financial report', [
                'error' => $e->getMessage(),
                'filters' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate financial report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get fraud analysis report
     */
    public function getFraudAnalysisReport(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'date_from', 'date_to', 'gateway'
            ]);

            $report = $this->reportService->generateFraudAnalysisReport($filters);

            return response()->json([
                'success' => true,
                'data' => $report,
                'filters' => $filters,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate fraud analysis report', [
                'error' => $e->getMessage(),
                'filters' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate fraud analysis report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get customer behavior report
     */
    public function getCustomerBehaviorReport(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'date_from', 'date_to', 'user_id'
            ]);

            $report = $this->reportService->generateCustomerBehaviorReport($filters);

            return response()->json([
                'success' => true,
                'data' => $report,
                'filters' => $filters,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate customer behavior report', [
                'error' => $e->getMessage(),
                'filters' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate customer behavior report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export report
     */
    public function exportReport(Request $request): Response
    {
        try {
            $reportType = $request->input('type', 'transaction');
            $format = $request->input('format', 'json');
            $filters = $request->only([
                'date_from', 'date_to', 'gateway', 'status', 'currency'
            ]);

            $report = match ($reportType) {
                'transaction' => $this->reportService->generateTransactionReport($filters),
                'gateway_performance' => $this->reportService->generateGatewayPerformanceReport($filters),
                'financial' => $this->reportService->generateFinancialReport($filters),
                'fraud_analysis' => $this->reportService->generateFraudAnalysisReport($filters),
                'customer_behavior' => $this->reportService->generateCustomerBehaviorReport($filters),
                default => throw new \InvalidArgumentException("Invalid report type: {$reportType}"),
            };

            $exportedData = $this->reportService->exportReport($report, $format, $request->all());

            $filename = "report_{$reportType}_" . now()->format('Y-m-d_H-i-s') . ".{$format}";

            return response($exportedData)
                ->header('Content-Type', $this->getContentType($format))
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        } catch (\Exception $e) {
            Log::error('Failed to export report', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to export report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get dashboard data
     */
    public function getDashboardData(): JsonResponse
    {
        try {
            $data = $this->reportService->getDashboardData();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get dashboard data', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get dashboard data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate QR code for payment
     */
    public function generatePaymentQRCode(Request $request): JsonResponse
    {
        try {
            $paymentId = $request->input('payment_id');
            $options = $request->only([
                'size', 'format', 'error_correction', 'margin', 'logo', 'style'
            ]);

            if (!$paymentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment ID is required',
                ], 400);
            }

            $payment = \BDPayments\LaravelPaymentGateway\Models\Payment::findOrFail($paymentId);
            $qrCode = $this->qrCodeService->generatePaymentQRCode($payment, $options);

            return response()->json([
                'success' => true,
                'data' => [
                    'qr_code' => base64_encode($qrCode),
                    'payment_id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate payment QR code', [
                'error' => $e->getMessage(),
                'payment_id' => $request->input('payment_id'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate payment QR code',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate QR code for payment URL
     */
    public function generatePaymentURLQRCode(Request $request): JsonResponse
    {
        try {
            $paymentUrl = $request->input('payment_url');
            $options = $request->only([
                'size', 'format', 'error_correction', 'margin', 'logo', 'style'
            ]);

            if (!$paymentUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment URL is required',
                ], 400);
            }

            $qrCode = $this->qrCodeService->generatePaymentURLQRCode($paymentUrl, $options);

            return response()->json([
                'success' => true,
                'data' => [
                    'qr_code' => base64_encode($qrCode),
                    'payment_url' => $paymentUrl,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate payment URL QR code', [
                'error' => $e->getMessage(),
                'payment_url' => $request->input('payment_url'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate payment URL QR code',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate QR code for invoice
     */
    public function generateInvoiceQRCode(Request $request): JsonResponse
    {
        try {
            $paymentId = $request->input('payment_id');
            $options = $request->only([
                'size', 'format', 'error_correction', 'margin', 'logo', 'style'
            ]);

            if (!$paymentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment ID is required',
                ], 400);
            }

            $payment = \BDPayments\LaravelPaymentGateway\Models\Payment::findOrFail($paymentId);
            $qrCode = $this->qrCodeService->generateInvoiceQRCode($payment, $options);

            return response()->json([
                'success' => true,
                'data' => [
                    'qr_code' => base64_encode($qrCode),
                    'payment_id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate invoice QR code', [
                'error' => $e->getMessage(),
                'payment_id' => $request->input('payment_id'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate invoice QR code',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate QR code for refund
     */
    public function generateRefundQRCode(Request $request): JsonResponse
    {
        try {
            $paymentId = $request->input('payment_id');
            $amount = $request->input('amount');
            $reason = $request->input('reason', '');
            $options = $request->only([
                'size', 'format', 'error_correction', 'margin', 'logo', 'style'
            ]);

            if (!$paymentId || !$amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment ID and amount are required',
                ], 400);
            }

            $payment = \BDPayments\LaravelPaymentGateway\Models\Payment::findOrFail($paymentId);
            $qrCode = $this->qrCodeService->generateRefundQRCode($payment, $amount, $reason, $options);

            return response()->json([
                'success' => true,
                'data' => [
                    'qr_code' => base64_encode($qrCode),
                    'payment_id' => $payment->id,
                    'refund_amount' => $amount,
                    'reason' => $reason,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate refund QR code', [
                'error' => $e->getMessage(),
                'payment_id' => $request->input('payment_id'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate refund QR code',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate QR code for receipt
     */
    public function generateReceiptQRCode(Request $request): JsonResponse
    {
        try {
            $paymentId = $request->input('payment_id');
            $options = $request->only([
                'size', 'format', 'error_correction', 'margin', 'logo', 'style'
            ]);

            if (!$paymentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment ID is required',
                ], 400);
            }

            $payment = \BDPayments\LaravelPaymentGateway\Models\Payment::findOrFail($paymentId);
            $qrCode = $this->qrCodeService->generateReceiptQRCode($payment, $options);

            return response()->json([
                'success' => true,
                'data' => [
                    'qr_code' => base64_encode($qrCode),
                    'payment_id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate receipt QR code', [
                'error' => $e->getMessage(),
                'payment_id' => $request->input('payment_id'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate receipt QR code',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate custom QR code
     */
    public function generateCustomQRCode(Request $request): JsonResponse
    {
        try {
            $data = $request->input('data');
            $options = $request->only([
                'size', 'format', 'error_correction', 'margin', 'logo', 'style'
            ]);

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data is required',
                ], 400);
            }

            $qrCode = $this->qrCodeService->generateCustomQRCode($data, $options);

            return response()->json([
                'success' => true,
                'data' => [
                    'qr_code' => base64_encode($qrCode),
                    'data' => $data,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate custom QR code', [
                'error' => $e->getMessage(),
                'data' => $request->input('data'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate custom QR code',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get QR code statistics
     */
    public function getQRCodeStats(): JsonResponse
    {
        try {
            $stats = $this->qrCodeService->getQRCodeStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get QR code statistics', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get QR code statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clean up old QR codes
     */
    public function cleanupOldQRCodes(Request $request): JsonResponse
    {
        try {
            $daysOld = $request->input('days_old', 30);
            $deletedCount = $this->qrCodeService->cleanupOldQRCodes($daysOld);

            return response()->json([
                'success' => true,
                'message' => "Cleaned up {$deletedCount} old QR codes",
                'deleted_count' => $deletedCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cleanup old QR codes', [
                'error' => $e->getMessage(),
                'days_old' => $request->input('days_old', 30),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup old QR codes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get content type for export format
     */
    private function getContentType(string $format): string
    {
        return match ($format) {
            'json' => 'application/json',
            'csv' => 'text/csv',
            'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };
    }
}

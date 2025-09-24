<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Http\Controllers;

use BDPayments\LaravelPaymentGateway\Services\QRCodeService;
use BDPayments\LaravelPaymentGateway\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class QRCodeController extends Controller
{
    public function __construct(
        private readonly QRCodeService $qrCodeService
    ) {}

    /**
     * Generate QR code for payment
     */
    public function generatePaymentQRCode(Request $request): JsonResponse
    {
        try {
            $paymentId = $request->input('payment_id');
            $options = $request->only([
                'size', 'format', 'error_correction', 'margin', 'logo', 'style', 'store'
            ]);

            if (!$paymentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment ID is required',
                ], 400);
            }

            $payment = Payment::findOrFail($paymentId);
            $qrCode = $this->qrCodeService->generatePaymentQRCode($payment, $options);

            return response()->json([
                'success' => true,
                'data' => [
                    'qr_code' => base64_encode($qrCode),
                    'payment_id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'gateway' => $payment->gateway,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
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
     * Generate QR code for payment data
     */
    public function generatePaymentDataQRCode(Request $request): JsonResponse
    {
        try {
            $paymentData = $request->input('payment_data', []);
            $options = $request->only([
                'size', 'format', 'error_correction', 'margin', 'logo', 'style'
            ]);

            if (empty($paymentData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment data is required',
                ], 400);
            }

            $qrCode = $this->qrCodeService->generatePaymentDataQRCode($paymentData, $options);

            return response()->json([
                'success' => true,
                'data' => [
                    'qr_code' => base64_encode($qrCode),
                    'payment_data' => $paymentData,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate payment data QR code', [
                'error' => $e->getMessage(),
                'payment_data' => $request->input('payment_data'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate payment data QR code',
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

            $payment = Payment::findOrFail($paymentId);
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

            $payment = Payment::findOrFail($paymentId);
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

            $payment = Payment::findOrFail($paymentId);
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
     * Generate QR code with logo
     */
    public function generateQRCodeWithLogo(Request $request): JsonResponse
    {
        try {
            $data = $request->input('data');
            $logoPath = $request->input('logo_path');
            $options = $request->only([
                'size', 'format', 'error_correction', 'margin', 'style'
            ]);

            if (!$data || !$logoPath) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data and logo path are required',
                ], 400);
            }

            $qrCode = $this->qrCodeService->generateQRCodeWithLogo($data, $logoPath, $options);

            return response()->json([
                'success' => true,
                'data' => [
                    'qr_code' => base64_encode($qrCode),
                    'data' => $data,
                    'logo_path' => $logoPath,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate QR code with logo', [
                'error' => $e->getMessage(),
                'data' => $request->input('data'),
                'logo_path' => $request->input('logo_path'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate QR code with logo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate styled QR code
     */
    public function generateStyledQRCode(Request $request): JsonResponse
    {
        try {
            $data = $request->input('data');
            $style = $request->input('style', []);
            $options = $request->only([
                'size', 'format', 'error_correction', 'margin'
            ]);

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data is required',
                ], 400);
            }

            $qrCode = $this->qrCodeService->generateStyledQRCode($data, $style, $options);

            return response()->json([
                'success' => true,
                'data' => [
                    'qr_code' => base64_encode($qrCode),
                    'data' => $data,
                    'style' => $style,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate styled QR code', [
                'error' => $e->getMessage(),
                'data' => $request->input('data'),
                'style' => $request->input('style'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate styled QR code',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate batch QR codes
     */
    public function generateBatchQRCodes(Request $request): JsonResponse
    {
        try {
            $paymentIds = $request->input('payment_ids', []);
            $options = $request->only([
                'size', 'format', 'error_correction', 'margin', 'logo', 'style'
            ]);

            if (empty($paymentIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment IDs are required',
                ], 400);
            }

            $payments = Payment::whereIn('id', $paymentIds)->get();
            $qrCodes = $this->qrCodeService->generateBatchQRCodes($payments->toArray(), $options);

            return response()->json([
                'success' => true,
                'data' => [
                    'qr_codes' => $qrCodes,
                    'payment_count' => count($qrCodes),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate batch QR codes', [
                'error' => $e->getMessage(),
                'payment_ids' => $request->input('payment_ids'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate batch QR codes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate QR code data
     */
    public function validateQRCodeData(Request $request): JsonResponse
    {
        try {
            $qrData = $request->input('qr_data');

            if (!$qrData) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR data is required',
                ], 400);
            }

            $isValid = $this->qrCodeService->validateQRCodeData($qrData);

            return response()->json([
                'success' => true,
                'data' => [
                    'is_valid' => $isValid,
                    'qr_data' => $qrData,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to validate QR code data', [
                'error' => $e->getMessage(),
                'qr_data' => $request->input('qr_data'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to validate QR code data',
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
}

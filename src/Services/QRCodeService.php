<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Services;

use BDPayments\LaravelPaymentGateway\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QRCodeService
{
    private array $config;

    public function __construct()
    {
        $this->config = config('payment-gateway.qr_code', []);
    }

    /**
     * Generate QR code for payment
     */
    public function generatePaymentQRCode(Payment $payment, array $options = []): string
    {
        $qrData = $this->buildPaymentQRData($payment, $options);
        $qrCode = $this->generateQRCode($qrData, $options);
        
        // Store QR code if requested
        if ($options['store'] ?? false) {
            $this->storeQRCode($payment, $qrCode, $options);
        }

        return $qrCode;
    }

    /**
     * Generate QR code for payment URL
     */
    public function generatePaymentURLQRCode(string $paymentUrl, array $options = []): string
    {
        return $this->generateQRCode($paymentUrl, $options);
    }

    /**
     * Generate QR code for payment data
     */
    public function generatePaymentDataQRCode(array $paymentData, array $options = []): string
    {
        $qrData = $this->formatPaymentDataForQR($paymentData);
        return $this->generateQRCode($qrData, $options);
    }

    /**
     * Generate QR code for invoice
     */
    public function generateInvoiceQRCode(Payment $payment, array $options = []): string
    {
        $invoiceData = $this->buildInvoiceQRData($payment, $options);
        return $this->generateQRCode($invoiceData, $options);
    }

    /**
     * Generate QR code for refund
     */
    public function generateRefundQRCode(Payment $payment, float $amount, string $reason = '', array $options = []): string
    {
        $refundData = $this->buildRefundQRData($payment, $amount, $reason, $options);
        return $this->generateQRCode($refundData, $options);
    }

    /**
     * Generate QR code for payment receipt
     */
    public function generateReceiptQRCode(Payment $payment, array $options = []): string
    {
        $receiptData = $this->buildReceiptQRData($payment, $options);
        return $this->generateQRCode($receiptData, $options);
    }

    /**
     * Generate QR code with custom data
     */
    public function generateCustomQRCode(string $data, array $options = []): string
    {
        return $this->generateQRCode($data, $options);
    }

    /**
     * Generate QR code with logo
     */
    public function generateQRCodeWithLogo(string $data, string $logoPath, array $options = []): string
    {
        $options['logo'] = $logoPath;
        return $this->generateQRCode($data, $options);
    }

    /**
     * Generate QR code with custom styling
     */
    public function generateStyledQRCode(string $data, array $style, array $options = []): string
    {
        $options['style'] = $style;
        return $this->generateQRCode($data, $options);
    }

    /**
     * Generate multiple QR codes for batch processing
     */
    public function generateBatchQRCodes(array $payments, array $options = []): array
    {
        $qrCodes = [];

        foreach ($payments as $payment) {
            if ($payment instanceof Payment) {
                $qrCodes[$payment->id] = $this->generatePaymentQRCode($payment, $options);
            }
        }

        return $qrCodes;
    }

    /**
     * Validate QR code data
     */
    public function validateQRCodeData(string $qrData): bool
    {
        // Basic validation for QR code data
        if (empty($qrData) || strlen($qrData) > 4000) {
            return false;
        }

        // Check for valid payment data structure
        if (str_contains($qrData, 'payment_id') || str_contains($qrData, 'transaction_id')) {
            return true;
        }

        // Check for valid URL
        if (filter_var($qrData, FILTER_VALIDATE_URL)) {
            return true;
        }

        return true; // Allow custom data
    }

    /**
     * Get QR code statistics
     */
    public function getQRCodeStats(): array
    {
        $storagePath = $this->config['storage_path'] ?? 'qr-codes';
        $files = Storage::files($storagePath);

        return [
            'total_qr_codes' => count($files),
            'storage_path' => $storagePath,
            'storage_size' => $this->getStorageSize($files),
            'last_generated' => $this->getLastGeneratedQRCode($files),
        ];
    }

    /**
     * Clean up old QR codes
     */
    public function cleanupOldQRCodes(int $daysOld = 30): int
    {
        $storagePath = $this->config['storage_path'] ?? 'qr-codes';
        $files = Storage::files($storagePath);
        $deletedCount = 0;

        foreach ($files as $file) {
            $lastModified = Storage::lastModified($file);
            $daysSinceModified = (time() - $lastModified) / (24 * 60 * 60);

            if ($daysSinceModified > $daysOld) {
                Storage::delete($file);
                $deletedCount++;
            }
        }

        Log::info('QR code cleanup completed', [
            'deleted_count' => $deletedCount,
            'days_old' => $daysOld,
        ]);

        return $deletedCount;
    }

    /**
     * Build payment QR data
     */
    private function buildPaymentQRData(Payment $payment, array $options): string
    {
        $data = [
            'payment_id' => $payment->id,
            'transaction_id' => $payment->transaction_id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'gateway' => $payment->gateway,
            'status' => $payment->status,
            'created_at' => $payment->created_at->toISOString(),
        ];

        if ($options['include_url'] ?? false) {
            $data['payment_url'] = route('payment-gateway.payment.show', $payment->id);
        }

        if ($options['include_customer'] ?? false && $payment->customer_data) {
            $data['customer'] = $payment->customer_data;
        }

        return json_encode($data);
    }

    /**
     * Build invoice QR data
     */
    private function buildInvoiceQRData(Payment $payment, array $options): string
    {
        $data = [
            'type' => 'invoice',
            'payment_id' => $payment->id,
            'transaction_id' => $payment->transaction_id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'gateway' => $payment->gateway,
            'invoice_url' => route('payment-gateway.invoice.show', $payment->id),
        ];

        return json_encode($data);
    }

    /**
     * Build refund QR data
     */
    private function buildRefundQRData(Payment $payment, float $amount, string $reason, array $options): string
    {
        $data = [
            'type' => 'refund',
            'payment_id' => $payment->id,
            'transaction_id' => $payment->transaction_id,
            'refund_amount' => $amount,
            'currency' => $payment->currency,
            'reason' => $reason,
            'refund_url' => route('payment-gateway.refund.create', $payment->id),
        ];

        return json_encode($data);
    }

    /**
     * Build receipt QR data
     */
    private function buildReceiptQRData(Payment $payment, array $options): string
    {
        $data = [
            'type' => 'receipt',
            'payment_id' => $payment->id,
            'transaction_id' => $payment->transaction_id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'gateway' => $payment->gateway,
            'receipt_url' => route('payment-gateway.receipt.show', $payment->id),
        ];

        return json_encode($data);
    }

    /**
     * Format payment data for QR code
     */
    private function formatPaymentDataForQR(array $paymentData): string
    {
        return json_encode($paymentData);
    }

    /**
     * Generate QR code
     */
    private function generateQRCode(string $data, array $options): string
    {
        $size = $options['size'] ?? $this->config['size'] ?? 200;
        $format = $options['format'] ?? $this->config['format'] ?? 'png';
        $errorCorrection = $options['error_correction'] ?? $this->config['error_correction'] ?? 'M';
        $margin = $options['margin'] ?? $this->config['margin'] ?? 1;

        $qrCode = QrCode::format($format)
            ->size($size)
            ->errorCorrection($errorCorrection)
            ->margin($margin);

        // Add logo if specified
        if (isset($options['logo']) && file_exists($options['logo'])) {
            $qrCode->merge($options['logo'], 0.2, true);
        }

        // Apply custom styling
        if (isset($options['style'])) {
            $style = $options['style'];
            
            if (isset($style['color'])) {
                $qrCode->color($style['color'][0], $style['color'][1], $style['color'][2]);
            }
            
            if (isset($style['background_color'])) {
                $qrCode->backgroundColor($style['background_color'][0], $style['background_color'][1], $style['background_color'][2]);
            }
        }

        return $qrCode->generate($data);
    }

    /**
     * Store QR code
     */
    private function storeQRCode(Payment $payment, string $qrCode, array $options): void
    {
        $storagePath = $this->config['storage_path'] ?? 'qr-codes';
        $filename = "payment_{$payment->id}_" . time() . '.png';
        $filePath = "{$storagePath}/{$filename}";

        Storage::put($filePath, $qrCode);

        // Update payment with QR code path
        $payment->update([
            'qr_code_path' => $filePath,
        ]);

        Log::info('QR code stored', [
            'payment_id' => $payment->id,
            'file_path' => $filePath,
        ]);
    }

    /**
     * Get storage size
     */
    private function getStorageSize(array $files): int
    {
        $totalSize = 0;

        foreach ($files as $file) {
            $totalSize += Storage::size($file);
        }

        return $totalSize;
    }

    /**
     * Get last generated QR code
     */
    private function getLastGeneratedQRCode(array $files): ?string
    {
        if (empty($files)) {
            return null;
        }

        $lastModified = 0;
        $lastFile = null;

        foreach ($files as $file) {
            $modified = Storage::lastModified($file);
            if ($modified > $lastModified) {
                $lastModified = $modified;
                $lastFile = $file;
            }
        }

        return $lastFile;
    }
}

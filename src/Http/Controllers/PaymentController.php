<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use BDPayments\LaravelPaymentGateway\Services\PaymentGatewayService;
use BDPayments\LaravelPaymentGateway\Services\PaymentLogger;
use BDPayments\LaravelPaymentGateway\Exceptions\PaymentException;
use BDPayments\LaravelPaymentGateway\Exceptions\ValidationException;
use BDPayments\LaravelPaymentGateway\Exceptions\ConfigurationException;
use BDPayments\LaravelPaymentGateway\Exceptions\NetworkException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayService $paymentService,
        private readonly PaymentLogger $logger
    ) {}

    /**
     * Show payment form
     */
    public function showPaymentForm(Request $request): View
    {
        $gateway = $request->get('gateway', config('payment-gateway.default_gateway', 'nagad'));
        $supportedGateways = $this->paymentService->getSupportedGateways();

        return view('payment-gateway::payment-form', [
            'gateway' => $gateway,
            'supportedGateways' => $supportedGateways,
            'amount' => $request->get('amount'),
            'orderId' => $request->get('order_id'),
            'currency' => $request->get('currency', 'BDT'),
        ]);
    }

    /**
     * Initialize payment
     */
    public function initializePayment(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'gateway' => 'required|string|in:nagad,bkash,binance',
                'order_id' => 'required|string|max:50',
                'amount' => 'required|numeric|min:0.01',
                'currency' => 'string|max:10',
                'callback_url' => 'nullable|url',
                'return_url' => 'nullable|url',
                'notify_url' => 'nullable|url',
                'product_name' => 'nullable|string|max:255',
                'product_detail' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $paymentData = $validator->validated();
            $gateway = $paymentData['gateway'];

            // Add additional data based on gateway
            if ($gateway === 'binance') {
                $paymentData['product_name'] = $paymentData['product_name'] ?? 'Payment';
                $paymentData['product_detail'] = $paymentData['product_detail'] ?? '';
                $paymentData['return_url'] = $paymentData['return_url'] ?? $paymentData['callback_url'];
                $paymentData['notify_url'] = $paymentData['notify_url'] ?? route('payment.webhook', ['gateway' => $gateway]);
            } else {
                $paymentData['callback_url'] = $paymentData['callback_url'] ?? route('payment.callback', ['gateway' => $gateway]);
            }

            $response = $this->paymentService->initializePayment($gateway, $paymentData);

            // Log payment initialization
            $this->logger->logPaymentInitialization($gateway, $paymentData, $response);

            return response()->json([
                'success' => $response->success,
                'message' => $response->message,
                'data' => $response->data,
                'payment_id' => $response->paymentId,
                'redirect_url' => $response->redirectUrl,
                'transaction_id' => $response->transactionId,
                'amount' => $response->amount,
                'currency' => $response->currency,
                'status' => $response->status,
            ]);

        } catch (ValidationException $e) {
            Log::warning('Payment validation error', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage(),
            ], 422);

        } catch (ConfigurationException $e) {
            Log::error('Payment configuration error', [
                'error' => $e->getMessage(),
                'gateway' => $request->get('gateway')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Configuration error: ' . $e->getMessage(),
            ], 500);

        } catch (NetworkException $e) {
            Log::error('Payment network error', [
                'error' => $e->getMessage(),
                'gateway' => $request->get('gateway')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Network error: ' . $e->getMessage(),
            ], 503);

        } catch (PaymentException $e) {
            Log::error('Payment error', [
                'error' => $e->getMessage(),
                'gateway' => $request->get('gateway')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment error: ' . $e->getMessage(),
            ], 400);

        } catch (\Exception $e) {
            Log::error('Unexpected payment error', [
                'error' => $e->getMessage(),
                'gateway' => $request->get('gateway')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
            ], 500);
        }
    }

    /**
     * Verify payment
     */
    public function verifyPayment(Request $request, string $gateway): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $paymentId = $request->get('payment_id');
            $response = $this->paymentService->verifyPayment($gateway, $paymentId);

            // Log payment verification
            $this->logger->logPaymentVerification($gateway, $paymentId, $response);

            return response()->json([
                'success' => $response->success,
                'message' => $response->message,
                'data' => $response->data,
                'payment_id' => $response->paymentId,
                'transaction_id' => $response->transactionId,
                'amount' => $response->amount,
                'currency' => $response->currency,
                'status' => $response->status,
            ]);

        } catch (PaymentException $e) {
            Log::error('Payment verification error', [
                'error' => $e->getMessage(),
                'gateway' => $gateway,
                'payment_id' => $request->get('payment_id')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment verification error: ' . $e->getMessage(),
            ], 400);

        } catch (\Exception $e) {
            Log::error('Unexpected payment verification error', [
                'error' => $e->getMessage(),
                'gateway' => $gateway,
                'payment_id' => $request->get('payment_id')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
            ], 500);
        }
    }

    /**
     * Refund payment
     */
    public function refundPayment(Request $request, string $gateway): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_id' => 'required|string',
                'amount' => 'required|numeric|min:0.01',
                'reason' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $paymentId = $request->get('payment_id');
            $amount = (float) $request->get('amount');
            $reason = $request->get('reason', '');

            $response = $this->paymentService->refundPayment($gateway, $paymentId, $amount, $reason);

            // Log payment refund
            $this->logger->logPaymentRefund($gateway, $paymentId, $amount, $reason, $response);

            return response()->json([
                'success' => $response->success,
                'message' => $response->message,
                'data' => $response->data,
                'refund_id' => $response->paymentId,
                'transaction_id' => $response->transactionId,
                'amount' => $response->amount,
                'currency' => $response->currency,
                'status' => $response->status,
            ]);

        } catch (PaymentException $e) {
            Log::error('Payment refund error', [
                'error' => $e->getMessage(),
                'gateway' => $gateway,
                'payment_id' => $request->get('payment_id')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment refund error: ' . $e->getMessage(),
            ], 400);

        } catch (\Exception $e) {
            Log::error('Unexpected payment refund error', [
                'error' => $e->getMessage(),
                'gateway' => $gateway,
                'payment_id' => $request->get('payment_id')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
            ], 500);
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(Request $request, string $gateway): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $paymentId = $request->get('payment_id');
            $response = $this->paymentService->getPaymentStatus($gateway, $paymentId);

            // Log payment status check
            $this->logger->logPaymentStatus($gateway, $paymentId, $response);

            return response()->json([
                'success' => $response->success,
                'message' => $response->message,
                'data' => $response->data,
                'payment_id' => $response->paymentId,
                'transaction_id' => $response->transactionId,
                'amount' => $response->amount,
                'currency' => $response->currency,
                'status' => $response->status,
            ]);

        } catch (PaymentException $e) {
            Log::error('Payment status check error', [
                'error' => $e->getMessage(),
                'gateway' => $gateway,
                'payment_id' => $request->get('payment_id')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment status check error: ' . $e->getMessage(),
            ], 400);

        } catch (\Exception $e) {
            Log::error('Unexpected payment status check error', [
                'error' => $e->getMessage(),
                'gateway' => $gateway,
                'payment_id' => $request->get('payment_id')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
            ], 500);
        }
    }

    /**
     * Handle payment callback
     */
    public function handleCallback(Request $request, string $gateway): RedirectResponse
    {
        try {
            $paymentId = $request->get('payment_id') ?? $request->get('prepayId');
            
            if (!$paymentId) {
                return redirect()->route('payment.failed', [
                    'gateway' => $gateway,
                    'error' => 'Payment ID not found'
                ]);
            }

            $response = $this->paymentService->verifyPayment($gateway, $paymentId);

            if ($response->success) {
                return redirect()->route('payment.success', [
                    'gateway' => $gateway,
                    'payment_id' => $paymentId,
                    'amount' => $response->amount,
                    'currency' => $response->currency,
                ]);
            } else {
                return redirect()->route('payment.failed', [
                    'gateway' => $gateway,
                    'error' => $response->message
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Payment callback error', [
                'error' => $e->getMessage(),
                'gateway' => $gateway,
                'request_data' => $request->all()
            ]);

            return redirect()->route('payment.failed', [
                'gateway' => $gateway,
                'error' => 'Payment verification failed'
            ]);
        }
    }

    /**
     * Handle payment webhook
     */
    public function handleWebhook(Request $request, string $gateway): JsonResponse
    {
        try {
            // Verify webhook signature if configured
            $this->verifyWebhookSignature($request, $gateway);

            $payload = $request->all();
            
            // Log webhook data
            Log::info("Payment webhook received from {$gateway}", [
                'gateway' => $gateway,
                'payload' => $payload
            ]);

            // Process webhook based on gateway
            $this->processWebhook($gateway, $payload);

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'error' => $e->getMessage(),
                'gateway' => $gateway,
                'payload' => $request->all()
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Show payment success page
     */
    public function showSuccess(Request $request): View
    {
        return view('payment-gateway::success', [
            'gateway' => $request->get('gateway'),
            'payment_id' => $request->get('payment_id'),
            'amount' => $request->get('amount'),
            'currency' => $request->get('currency'),
        ]);
    }

    /**
     * Show payment failed page
     */
    public function showFailed(Request $request): View
    {
        return view('payment-gateway::failed', [
            'gateway' => $request->get('gateway'),
            'error' => $request->get('error'),
        ]);
    }

    /**
     * Verify webhook signature
     */
    private function verifyWebhookSignature(Request $request, string $gateway): void
    {
        $secret = config("payment-gateway.webhooks.{$gateway}.secret");
        
        if (!$secret) {
            return; // No signature verification configured
        }

        $signature = $request->header('X-Webhook-Signature');
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            throw new \Exception('Invalid webhook signature');
        }
    }

    /**
     * Process webhook payload
     */
    private function processWebhook(string $gateway, array $payload): void
    {
        // Implement webhook processing logic based on gateway
        // This is where you would update your database, send notifications, etc.
        
        Log::info("Processing webhook for {$gateway}", [
            'gateway' => $gateway,
            'payload' => $payload
        ]);
    }
}

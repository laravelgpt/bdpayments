<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Http\Middleware;

use BDPayments\LaravelPaymentGateway\Services\PaymentSecurityService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PaymentSecurityMiddleware
{
    private PaymentSecurityService $securityService;

    public function __construct(PaymentSecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check rate limiting
        $identifier = $this->getRateLimitIdentifier($request);
        if (!$this->securityService->checkRateLimit($identifier)) {
            Log::warning('Payment rate limit exceeded', [
                'identifier' => $identifier,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Too many payment attempts. Please try again later.',
                'error_code' => 'RATE_LIMIT_EXCEEDED',
            ], 429);
        }

        // Fraud detection
        $fraudIndicators = $this->securityService->detectFraudulentActivity(
            $request->ip(),
            $request->all()
        );

        if (!empty($fraudIndicators)) {
            Log::warning('Fraudulent activity detected', [
                'indicators' => $fraudIndicators,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'data' => $this->securityService->sanitizeForLogging($request->all()),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Suspicious activity detected. Payment blocked.',
                'error_code' => 'FRAUD_DETECTED',
            ], 403);
        }

        // CSRF protection for payment forms
        if ($request->isMethod('POST') && $request->has('payment_nonce')) {
            if (!$this->securityService->verifyNonce($request->input('payment_nonce'))) {
                Log::warning('Invalid payment nonce', [
                    'ip' => $request->ip(),
                    'nonce' => $request->input('payment_nonce'),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid security token. Please refresh and try again.',
                    'error_code' => 'INVALID_NONCE',
                ], 403);
            }
        }

        // Validate payment hash for tampering protection
        if ($request->has('payment_hash') && $request->has('amount')) {
            $paymentData = $request->only(['amount', 'currency', 'reference_id', 'gateway']);
            $providedHash = $request->input('payment_hash');

            if (!$this->securityService->verifyPaymentHash($paymentData, $providedHash)) {
                Log::warning('Payment hash verification failed', [
                    'ip' => $request->ip(),
                    'provided_hash' => $providedHash,
                    'payment_data' => $paymentData,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment data integrity check failed.',
                    'error_code' => 'HASH_VERIFICATION_FAILED',
                ], 403);
            }
        }

        // Add security headers
        $response = $next($request);
        
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';");

        return $response;
    }

    /**
     * Get rate limit identifier
     */
    private function getRateLimitIdentifier(Request $request): string
    {
        $user = $request->user();
        
        if ($user) {
            return "user:{$user->id}";
        }
        
        return "ip:{$request->ip()}";
    }
}

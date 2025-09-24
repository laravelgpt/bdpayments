<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class WebhookSignatureMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $gateway = $request->route('gateway');
        $secret = config("payment-gateway.webhooks.{$gateway}.secret");
        
        if (!$secret) {
            Log::warning('Webhook signature verification disabled', [
                'gateway' => $gateway,
                'url' => $request->fullUrl(),
            ]);
            
            return $next($request);
        }

        $signature = $request->header('X-Webhook-Signature');
        $payload = $request->getContent();
        
        if (!$signature) {
            Log::warning('Missing webhook signature', [
                'gateway' => $gateway,
                'url' => $request->fullUrl(),
                'headers' => $request->headers->all(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Missing webhook signature',
            ], 401);
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        
        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('Invalid webhook signature', [
                'gateway' => $gateway,
                'expected' => $expectedSignature,
                'received' => $signature,
                'url' => $request->fullUrl(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook signature',
            ], 401);
        }

        Log::info('Webhook signature verified', [
            'gateway' => $gateway,
            'url' => $request->fullUrl(),
        ]);

        return $next($request);
    }
}

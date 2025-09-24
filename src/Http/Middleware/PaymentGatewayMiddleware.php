<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use BDPayments\LaravelPaymentGateway\Services\PaymentLogger;

class PaymentGatewayMiddleware
{
    public function __construct(
        private readonly PaymentLogger $logger
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Log incoming request
        $this->logger->log('request_received', 'middleware', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $request->headers->all(),
        ]);

        // Validate gateway parameter if present
        if ($request->route('gateway')) {
            $gateway = $request->route('gateway');
            $supportedGateways = ['nagad', 'bkash', 'binance'];
            
            if (!in_array($gateway, $supportedGateways)) {
                Log::warning('Invalid gateway in request', [
                    'gateway' => $gateway,
                    'supported_gateways' => $supportedGateways,
                    'url' => $request->fullUrl(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment gateway',
                ], 400);
            }
        }

        // Process request
        $response = $next($request);

        // Log response
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $this->logger->log('request_completed', 'middleware', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'response_size' => strlen($response->getContent()),
        ]);

        return $response;
    }
}

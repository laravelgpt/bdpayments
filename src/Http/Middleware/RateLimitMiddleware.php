<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 60, int $decayMinutes = 1): Response
    {
        $key = $this->resolveRequestSignature($request);
        $maxAttempts = $this->resolveMaxAttempts($request, $maxAttempts);
        $decayMinutes = $this->resolveDecayMinutes($request, $decayMinutes);

        if ($this->tooManyAttempts($key, $maxAttempts, $decayMinutes)) {
            Log::warning('Rate limit exceeded', [
                'key' => $key,
                'max_attempts' => $maxAttempts,
                'decay_minutes' => $decayMinutes,
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $decayMinutes * 60,
            ], 429);
        }

        $this->incrementAttempts($key, $decayMinutes);

        return $next($request);
    }

    /**
     * Resolve the request signature.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $gateway = $request->route('gateway');
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        
        return 'payment_gateway:' . md5($gateway . '|' . $ip . '|' . $userAgent);
    }

    /**
     * Resolve the maximum number of attempts.
     */
    protected function resolveMaxAttempts(Request $request, int $maxAttempts): int
    {
        $gateway = $request->route('gateway');
        $configKey = "payment-gateway.rate_limits.{$gateway}.max_attempts";
        
        return config($configKey, $maxAttempts);
    }

    /**
     * Resolve the decay minutes.
     */
    protected function resolveDecayMinutes(Request $request, int $decayMinutes): int
    {
        $gateway = $request->route('gateway');
        $configKey = "payment-gateway.rate_limits.{$gateway}.decay_minutes";
        
        return config($configKey, $decayMinutes);
    }

    /**
     * Determine if the request has too many attempts.
     */
    protected function tooManyAttempts(string $key, int $maxAttempts, int $decayMinutes): bool
    {
        $attempts = Cache::get($key, 0);
        return $attempts >= $maxAttempts;
    }

    /**
     * Increment the number of attempts.
     */
    protected function incrementAttempts(string $key, int $decayMinutes): void
    {
        Cache::increment($key);
        Cache::expire($key, $decayMinutes * 60);
    }
}

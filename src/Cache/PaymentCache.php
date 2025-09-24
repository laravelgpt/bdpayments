<?php

declare(strict_types=1);

namespace BDPayments\LaravelPaymentGateway\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PaymentCache
{
    private string $prefix;
    private int $defaultTtl;

    public function __construct()
    {
        $this->prefix = config('payment-gateway.cache.prefix', 'payment_gateway');
        $this->defaultTtl = config('payment-gateway.cache.ttl', 3600);
    }

    /**
     * Get cached payment data.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = $this->getCacheKey($key);
        
        try {
            return Cache::get($cacheKey, $default);
        } catch (\Exception $e) {
            Log::warning('Cache get failed', [
                'key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);
            
            return $default;
        }
    }

    /**
     * Store payment data in cache.
     */
    public function put(string $key, mixed $value, int $ttl = null): bool
    {
        $cacheKey = $this->getCacheKey($key);
        $ttl = $ttl ?? $this->defaultTtl;

        try {
            Cache::put($cacheKey, $value, $ttl);
            return true;
        } catch (\Exception $e) {
            Log::warning('Cache put failed', [
                'key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Store payment data in cache forever.
     */
    public function forever(string $key, mixed $value): bool
    {
        $cacheKey = $this->getCacheKey($key);

        try {
            Cache::forever($cacheKey, $value);
            return true;
        } catch (\Exception $e) {
            Log::warning('Cache forever failed', [
                'key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Remove payment data from cache.
     */
    public function forget(string $key): bool
    {
        $cacheKey = $this->getCacheKey($key);

        try {
            return Cache::forget($cacheKey);
        } catch (\Exception $e) {
            Log::warning('Cache forget failed', [
                'key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Check if payment data exists in cache.
     */
    public function has(string $key): bool
    {
        $cacheKey = $this->getCacheKey($key);

        try {
            return Cache::has($cacheKey);
        } catch (\Exception $e) {
            Log::warning('Cache has failed', [
                'key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Get or store payment data.
     */
    public function remember(string $key, callable $callback, int $ttl = null): mixed
    {
        $cacheKey = $this->getCacheKey($key);
        $ttl = $ttl ?? $this->defaultTtl;

        try {
            return Cache::remember($cacheKey, $ttl, $callback);
        } catch (\Exception $e) {
            Log::warning('Cache remember failed', [
                'key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);
            
            return $callback();
        }
    }

    /**
     * Get or store payment data forever.
     */
    public function rememberForever(string $key, callable $callback): mixed
    {
        $cacheKey = $this->getCacheKey($key);

        try {
            return Cache::rememberForever($cacheKey, $callback);
        } catch (\Exception $e) {
            Log::warning('Cache rememberForever failed', [
                'key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);
            
            return $callback();
        }
    }

    /**
     * Clear all payment cache.
     */
    public function flush(): bool
    {
        try {
            $pattern = $this->prefix . ':*';
            $keys = Cache::getRedis()->keys($pattern);
            
            if (!empty($keys)) {
                Cache::getRedis()->del($keys);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::warning('Cache flush failed', [
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Get cache statistics.
     */
    public function getStats(): array
    {
        try {
            $pattern = $this->prefix . ':*';
            $keys = Cache::getRedis()->keys($pattern);
            
            return [
                'total_keys' => count($keys),
                'prefix' => $this->prefix,
                'default_ttl' => $this->defaultTtl,
            ];
        } catch (\Exception $e) {
            Log::warning('Cache stats failed', [
                'error' => $e->getMessage(),
            ]);
            
            return [
                'total_keys' => 0,
                'prefix' => $this->prefix,
                'default_ttl' => $this->defaultTtl,
            ];
        }
    }

    /**
     * Cache payment gateway data.
     */
    public function cacheGatewayData(string $gateway, string $key, mixed $value, int $ttl = null): bool
    {
        $cacheKey = "gateway:{$gateway}:{$key}";
        return $this->put($cacheKey, $value, $ttl);
    }

    /**
     * Get cached payment gateway data.
     */
    public function getCachedGatewayData(string $gateway, string $key, mixed $default = null): mixed
    {
        $cacheKey = "gateway:{$gateway}:{$key}";
        return $this->get($cacheKey, $default);
    }

    /**
     * Cache payment transaction data.
     */
    public function cacheTransactionData(string $transactionId, mixed $value, int $ttl = null): bool
    {
        $cacheKey = "transaction:{$transactionId}";
        return $this->put($cacheKey, $value, $ttl);
    }

    /**
     * Get cached payment transaction data.
     */
    public function getCachedTransactionData(string $transactionId, mixed $default = null): mixed
    {
        $cacheKey = "transaction:{$transactionId}";
        return $this->get($cacheKey, $default);
    }

    /**
     * Cache payment rate limit data.
     */
    public function cacheRateLimitData(string $identifier, int $attempts, int $ttl): bool
    {
        $cacheKey = "rate_limit:{$identifier}";
        return $this->put($cacheKey, $attempts, $ttl);
    }

    /**
     * Get cached payment rate limit data.
     */
    public function getCachedRateLimitData(string $identifier): int
    {
        $cacheKey = "rate_limit:{$identifier}";
        return $this->get($cacheKey, 0);
    }

    /**
     * Cache AI analysis data.
     */
    public function cacheAIAnalysisData(string $paymentId, mixed $analysis, int $ttl = null): bool
    {
        $cacheKey = "ai_analysis:{$paymentId}";
        $ttl = $ttl ?? 1800; // 30 minutes default for AI analysis
        return $this->put($cacheKey, $analysis, $ttl);
    }

    /**
     * Get cached AI analysis data.
     */
    public function getCachedAIAnalysisData(string $paymentId, mixed $default = null): mixed
    {
        $cacheKey = "ai_analysis:{$paymentId}";
        return $this->get($cacheKey, $default);
    }

    /**
     * Get cache key with prefix.
     */
    private function getCacheKey(string $key): string
    {
        return $this->prefix . ':' . $key;
    }
}

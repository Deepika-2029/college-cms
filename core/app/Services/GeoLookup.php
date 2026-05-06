<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GeoLookup — resolves IP → country/city for audit logs.
 *
 * Uses ipapi.co (free, no key needed for ≤1000/day).
 * Results cached 24h per IP to stay within limits.
 * Falls back to null on any failure — never blocks a request.
 *
 * Rate-guard: tracks daily request count to avoid hammering the API.
 * If >900 calls have been made today, returns cached-only results.
 */
class GeoLookup
{
    private const CACHE_TTL       = 86400; // 24 hours per IP
    private const DAILY_LIMIT     = 900;   // stay under ipapi.co free tier (1000/day)
    private const DAILY_COUNT_KEY = 'geo_lookup_daily_count';
    private const REQUEST_TIMEOUT = 3;     // seconds

    public function lookup(string $ip): array
    {
        $empty = ['country' => null, 'city' => null];

        if ($this->isPrivate($ip)) {
            return ['country' => 'Local', 'city' => 'Private'];
        }

        $cacheKey = "geo:{$ip}";

        // Always return cached result if available
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Rate-guard: don't call API if daily limit approached
        $dailyCount = (int) Cache::get(self::DAILY_COUNT_KEY, 0);
        if ($dailyCount >= self::DAILY_LIMIT) {
            return $empty;
        }

        try {
            $resp = Http::timeout(self::REQUEST_TIMEOUT)
                ->withHeaders(['Accept' => 'application/json'])
                ->get("https://ipapi.co/{$ip}/json/");

            if ($resp->ok() && ! $resp->json('error')) {
                $result = [
                    'country' => $resp->json('country_name') ?? null,
                    'city'    => $resp->json('city')         ?? null,
                ];
                Cache::put($cacheKey, $result, self::CACHE_TTL);

                // Increment daily counter (reset each day)
                Cache::put(
                    self::DAILY_COUNT_KEY,
                    $dailyCount + 1,
                    now()->endOfDay()
                );

                return $result;
            }
        } catch (\Throwable $e) {
            // Network failure — log once per hour per IP to avoid log spam
            Cache::remember("geo_err:{$ip}", 3600, function () use ($ip, $e) {
                Log::debug("GeoLookup failed for {$ip}: " . $e->getMessage());
                return true;
            });
        }

        // Cache the failure too (short TTL) to avoid hammering on repeated failures
        Cache::put($cacheKey, $empty, 300); // 5 min
        return $empty;
    }

    private function isPrivate(string $ip): bool
    {
        return in_array($ip, ['127.0.0.1', '::1'], true)
            || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}

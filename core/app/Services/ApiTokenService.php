<?php

namespace App\Services;

use App\Models\PageTableLink;

/**
 * ApiTokenService — Short-lived HMAC-signed token vending machine.
 *
 * Tokens replace raw API keys in static/frontend pages.
 * A token is:
 *  - Server-signed with HMAC-SHA256 using APP_KEY (cannot be forged)
 *  - Table-scoped  (a "notices" token cannot fetch "faculty")
 *  - Time-limited  (default 15 minutes — useless after expiry)
 *  - Origin-pinned (bound to the requesting domain at issue time)
 *
 * Because tokens expire quickly, embedding one in JS memory is safe.
 * No permanent secret ever touches the client.
 */
class ApiTokenService
{
    private const TTL     = 900;   // 15 minutes in seconds
    private const VERSION = 'v1';  // bump to invalidate all tokens at once

    // ─── Token Generation ────────────────────────────────────────────────────

    /**
     * Issue a short-lived signed token for a specific table + origin.
     *
     * @param  string $table   The table name this token grants read access to
     * @param  string $origin  The request origin/host (e.g. "gpnainital.com")
     * @return array  ['token' => string, 'expires_in' => int, 'table' => string]
     */
    public function issue(string $table, string $origin): array
    {
        $expiresAt = time() + self::TTL;
        $payload   = $this->buildPayload($table, $origin, $expiresAt);
        $sig       = $this->sign($payload);

        $token = base64_encode($payload . '.' . $sig);

        return [
            'token'      => $token,
            'expires_in' => self::TTL,
            'table'      => $table,
        ];
    }

    // ─── Token Verification ──────────────────────────────────────────────────

    /**
     * Verify a token string.
     *
     * @param  string $token   Raw token from the request
     * @param  string $table   The table the request is trying to access
     * @param  string $origin  The current request's origin/host
     * @return bool
     */
    public function verify(string $token, string $table, string $origin): bool
    {
        try {
            $decoded = base64_decode($token, strict: true);
            if ($decoded === false) return false;

            // Split payload and signature (last 64 hex chars = SHA256 sig)
            $lastDot = strrpos($decoded, '.');
            if ($lastDot === false) return false;

            $payload = substr($decoded, 0, $lastDot);
            $sig     = substr($decoded, $lastDot + 1);

            // 1. Constant-time signature check (prevents timing attacks)
            $expected = $this->sign($payload);
            if (!hash_equals($expected, $sig)) return false;

            // 2. Parse payload
            $parts = explode('|', $payload);
            // Expected: version|table|origin|expires_at
            if (count($parts) !== 4) return false;
            [$ver, $tokenTable, $tokenOrigin, $expiresAt] = $parts;

            // 3. Version check
            if ($ver !== self::VERSION) return false;

            // 4. Expiry check
            if (time() > (int) $expiresAt) return false;

            // 5. Table scope check
            if (!hash_equals($tokenTable, $table)) return false;

            // 6. Origin check (case-insensitive host comparison)
            if (strtolower($tokenOrigin) !== strtolower($origin)) return false;

            return true;

        } catch (\Throwable) {
            return false;
        }
    }

    // ─── Page–Table Link Helpers ─────────────────────────────────────────────

    /**
     * Check if a page slug is authorised to access a given table.
     */
    public function pageCanAccessTable(string $slug, string $table): bool
    {
        return PageTableLink::where('page_slug', $slug)
            ->where('table_name', $table)
            ->exists();
    }

    /**
     * Return all tables a page slug is allowed to access.
     */
    public function tablesForPage(string $slug): array
    {
        return PageTableLink::where('page_slug', $slug)
            ->pluck('table_name')
            ->toArray();
    }

    // ─── Internals ───────────────────────────────────────────────────────────

    private function buildPayload(string $table, string $origin, int $expiresAt): string
    {
        return implode('|', [self::VERSION, $table, strtolower($origin), $expiresAt]);
    }

    private function sign(string $payload): string
    {
        $secret = config('app.key');
        // Strip "base64:" prefix if present (Laravel key format)
        if (str_starts_with($secret, 'base64:')) {
            $secret = base64_decode(substr($secret, 7));
        }
        return hash_hmac('sha256', $payload, $secret);
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\BlockedIp;
use App\Models\LoginAttempt;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * LoginThrottle — Progressive lockout:
 *  - 3 failures  → 2 min lockout
 *  - 5 failures  → 4 min lockout
 *  - 7 failures  → 6 min lockout
 *  - 10 failures → IP permanently blocked (24h auto-block)
 *  Each window resets after the lockout expires.
 */
class LoginThrottle
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip    = $request->ip();
        $email = strtolower(trim($request->input('email', '')));
        $key   = 'login_attempts|' . md5($email . '|' . $ip);

        // Load attempt record
        $attempts = (int) cache()->get($key, 0);

        // Check if currently locked out
        $lockKey = 'login_lock|' . md5($email . '|' . $ip);
        if (cache()->has($lockKey)) {
            $remainingSecs = cache()->get($lockKey . '_until', 0) - time();
            $remainingMins = max(1, (int) ceil($remainingSecs / 60));
            $this->logAttempt($request, false, "Locked out — {$remainingMins} min remaining");
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => "Too many login attempts. Try again in {$remainingMins} minute(s)."]);
        }

        $response = $next($request);

        $failed = $this->isFailedLogin($response, $request);

        if ($failed) {
            $attempts++;
            $lockMins = $this->getLockMinutes($attempts);

            // Persist attempt count (keep for 2 hours)
            cache()->put($key, $attempts, now()->addHours(2));

            // Apply lockout if threshold reached
            if ($lockMins > 0) {
                $until = time() + ($lockMins * 60);
                cache()->put($lockKey, true, now()->addMinutes($lockMins));
                cache()->put($lockKey . '_until', $until, now()->addMinutes($lockMins));
            }

            // Auto-block IP at 10+ attempts
            if ($attempts >= 10 && !BlockedIp::isBlocked($ip)) {
                BlockedIp::create([
                    'ip_address' => $ip,
                    'reason'     => "Auto-blocked: 10+ failed login attempts",
                    'blocked_by' => null,
                    'expires_at' => now()->addHours(24),
                ]);
                $this->logAttempt($request, false, "IP auto-blocked after {$attempts} attempts");
            } else {
                $this->logAttempt($request, false, "Failed attempt #{$attempts}" . ($lockMins > 0 ? " — locked {$lockMins}min" : ""));
            }

            // Progressive sleep to slow automated attacks
            $sleepSecs = min($attempts, 5);
            if ($sleepSecs > 0) sleep($sleepSecs);

        } else {
            // Successful login — clear counters
            cache()->forget($key);
            cache()->forget($lockKey);
            cache()->forget($lockKey . '_until');
        }

        return $response;
    }

    /**
     * Returns lockout minutes based on attempt count.
     * 3→2min, 5→4min, 7→6min, 8→8min, 9→10min, 10+→blocked
     */
    private function getLockMinutes(int $attempts): int
    {
        return match(true) {
            $attempts >= 10 => 0, // handled by IP block
            $attempts >= 9  => 10,
            $attempts >= 8  => 8,
            $attempts >= 7  => 6,
            $attempts >= 5  => 4,
            $attempts >= 3  => 2,
            default         => 0,
        };
    }

    private function isFailedLogin(Response $response, Request $request): bool
    {
        // Failed auth in Laravel always redirects with validation errors
        if ($response->getStatusCode() !== 302) return false;
        $location = $response->headers->get('Location', '');
        // A successful login redirects to dashboard, not back to login
        return str_contains($location, 'login') || str_contains($location, $request->url());
    }

    private function logAttempt(Request $request, bool $success, string $note): void
    {
        try {
            AuditLog::create([
                'user_id'      => null,
                'user_name'    => null,
                'user_email'   => $request->input('email'),
                'user_role'    => null,
                'action'       => $success ? 'login' : 'login.failed',
                'target_type'  => 'auth',
                'target_label' => $note . ' — IP: ' . $request->ip(),
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->userAgent(),
            ]);
        } catch (\Throwable) {}
    }
}

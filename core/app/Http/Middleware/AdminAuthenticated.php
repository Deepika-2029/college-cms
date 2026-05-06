<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\BlockedIp;
use App\Models\UserIpAllowlist;
use App\Services\AnomalyDetector;
use App\Services\AuditLogger;
use App\Services\GeoLookup;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * AdminAuthenticated
 *
 * Enforces:
 *  1. IP blocklist check
 *  2. Authentication required
 *  3. Per-user IP allowlist (if configured)
 *  4. Account must be active
 *  5. Absolute session timeout
 *  6. Session fingerprint (hijack detection)
 */
class AdminAuthenticated
{
    public function __construct(
        private AuditLogger     $audit,
        private AnomalyDetector $anomaly,
        private GeoLookup       $geo,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $ip = $request->ip();

        // 1. IP blocklist
        if (BlockedIp::isBlocked($ip)) {
            $this->logDenied($request, 'access.denied.blocked_ip');
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Access denied: your IP is blocked.'], 403);
            }
            abort(403, 'Access denied: your IP address has been blocked.');
        }

        // 2. Must be authenticated
        if (! Auth::check()) {
            if (! $request->routeIs('admin.login')) {
                $this->logDenied($request, 'access.denied.unauthenticated');
            }
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('admin.login')
                ->with('error', 'Please log in to access the admin panel.');
        }

        $user = Auth::user();

        // 3. Per-user IP allowlist check
        if (! UserIpAllowlist::isAllowed($user->id, $ip)) {
            $this->audit->log(
                'access.denied.ip_not_allowed',
                'auth',
                (string) $user->id,
                $user->email . ' — IP not in allowlist: ' . $ip
            );
            Auth::logout();
            $request->session()->invalidate();
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Access denied from this IP address.'], 403);
            }
            return redirect()->route('admin.login')
                ->with('error', 'Login from this IP address is not permitted for your account.');
        }

        // 4. Absolute session timeout
        $loginTime = Session::get('_admin_login_time');
        if ($loginTime === null) {
            Session::put('_admin_login_time', now()->timestamp);
        } else {
            $maxAge = (int) env('SESSION_ABSOLUTE_TIMEOUT', 480) * 60;
            if ((now()->timestamp - $loginTime) > $maxAge) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('admin.login')
                    ->with('error', 'Your session has expired. Please log in again.');
            }
        }

        // 5. Account must be active
        if (! $user->status) {
            Auth::logout();
            $request->session()->invalidate();
            $this->logDenied($request, 'access.denied.inactive_account');
            return redirect()->route('admin.login')
                ->with('error', 'Your account has been deactivated. Contact a Super Admin.');
        }

        // 6. Session fingerprint
        $fingerprint = $this->fingerprint($request);
        $storedFp    = Session::get('_admin_fp');

        if ($storedFp === null) {
            Session::put('_admin_fp', $fingerprint);
        } elseif (! hash_equals($storedFp, $fingerprint)) {
            $this->audit->log(
                'security.session_hijack_attempt',
                'session', null, $user->email,
                ['stored_fp' => substr($storedFp, 0, 8) . '…'],
                ['current_ip' => $ip]
            );
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('admin.login')
                ->with('error', 'Your session expired or was used from a different device.');
        }

        // 7. Periodic session ID rotation (every 15 minutes) — defence against session riding
        $lastRotation = \Illuminate\Support\Facades\Session::get('_admin_session_rotated', 0);
        if ((now()->timestamp - $lastRotation) > 900) { // 15 minutes
            $request->session()->regenerate(false); // regenerate ID, keep data
            \Illuminate\Support\Facades\Session::put('_admin_session_rotated', now()->timestamp);
        }

        return $next($request);
    }

    private function fingerprint(Request $request): string
    {
        $ua = substr($request->userAgent() ?? '', 0, 120);
        return hash_hmac('sha256', $request->ip() . '|' . $ua, config('app.key'));
    }

    private function logDenied(Request $request, string $action): void
    {
        try {
            $ip  = $request->ip();
            $geo = $this->geo->lookup($ip);

            $data = [
                'user_id'      => null,
                'user_name'    => null,
                'user_email'   => null,
                'user_role'    => null,
                'action'       => $action,
                'target_type'  => 'url',
                'target_label' => $request->path(),
                'ip_address'   => $ip,
                'user_agent'   => $request->userAgent(),
                'country'      => $geo['country'],
                'city'         => $geo['city'],
            ];

            [$suspicious, $reason] = $this->anomaly->analyse($data);

            AuditLog::create(array_merge($data, [
                'is_suspicious'     => $suspicious,
                'suspicious_reason' => $reason,
            ]));

            // Auto-block IP after 20 failed attempts in 1 hour
            if ($suspicious) {
                $recentDenials = AuditLog::where('ip_address', $ip)
                    ->whereIn('action', ['access.denied.unauthenticated', 'access.denied.blocked_ip', 'login.failed'])
                    ->where('created_at', '>=', now()->subHour())
                    ->count();

                if ($recentDenials >= 20 && ! BlockedIp::isBlocked($ip)) {
                    BlockedIp::create([
                        'ip_address' => $ip,
                        'reason'     => "Auto-blocked: {$recentDenials} failed attempts in 1 hour",
                        'blocked_by' => null,
                        'expires_at' => now()->addHours(24),
                    ]);
                }
            }
        } catch (\Throwable) {}
    }
}

<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * RequireRole — enforces role-based access on route groups.
 *
 * Usage in routes:
 *   ->middleware('role:super_admin')
 *   ->middleware('role:super_admin,admin')
 *
 * Returns 403 JSON for AJAX, redirects to dashboard for browser.
 * Every denial is audit-logged.
 */
class RequireRole
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('admin.login');
        }

        if (! in_array($user->role, $roles, true)) {
            // Audit the unauthorized attempt
            $this->audit->log(
                'access.forbidden',
                'url',
                null,
                $request->path(),
                null,
                ['required_roles' => $roles, 'user_role' => $user->role]
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'You do not have permission to perform this action.',
                ], 403);
            }

            return redirect()->route('admin.dashboard')
                ->with('error', 'You do not have permission to access that page.');
        }

        return $next($request);
    }
}

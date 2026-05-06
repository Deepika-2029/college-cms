<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * RequirePermission — enforces granular permission-based access.
 *
 * Usage in routes:
 *   ->middleware('permission:database_builder')
 */
class RequirePermission
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(Request $request, Closure $next, string $permission): mixed
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('admin.login');
        }

        if (! $user->hasSystemPermission($permission)) {
            // Audit the unauthorized attempt
            $this->audit->log(
                'access.forbidden.permission',
                'url',
                null,
                $request->path(),
                null,
                ['required_permission' => $permission]
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'You do not have permission to access this module.',
                ], 403);
            }

            return redirect()->route('admin.dashboard')
                ->with('error', "You do not have permission to access that page. (Missing: {$permission})");
        }

        return $next($request);
    }
}

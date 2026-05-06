<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BlockedIp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogController extends Controller
{
    // ── Main log view ─────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = AuditLog::with('user')->latest();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('user_email',  'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('action',    'like', "%{$search}%")
                  ->orWhere('target_label', 'like', "%{$search}%")
                  ->orWhere('ip_address',   'like', "%{$search}%");
            });
        }

        if ($action = $request->get('action')) {
            $query->where('action', 'like', "{$action}%");
        }

        if ($userId = $request->get('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($request->get('suspicious')) {
            $query->where('is_suspicious', true);
        }

        if ($from = $request->get('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->get('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs  = $query->paginate(50)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        // Security stats for the summary bar
        $stats = [
            'total'       => AuditLog::count(),
            'suspicious'  => AuditLog::where('is_suspicious', true)->count(),
            'failed_logins_today' => AuditLog::where('action', 'login.failed')
                                        ->whereDate('created_at', today())->count(),
            'blocked_ips' => BlockedIp::where(function($q){
                                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                            })->count(),
        ];

        $blockedIps = BlockedIp::with('blocker')->latest()->get();

        return view('admin.audit.index', compact('logs', 'users', 'stats', 'blockedIps'));
    }

    // ── Security: block an IP ─────────────────────────────────────────

    public function blockIp(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $data = $request->validate([
            'ip_address' => ['required', 'ip'],
            'reason'     => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        BlockedIp::updateOrCreate(
            ['ip_address' => $data['ip_address']],
            [
                'reason'     => $data['reason'] ?? 'Manually blocked',
                'blocked_by' => Auth::id(),
                'expires_at' => $data['expires_at'] ?? null,
            ]
        );

        // Log the block action
        AuditLog::create([
            'user_id'      => Auth::id(),
            'user_name'    => Auth::user()->name,
            'user_email'   => Auth::user()->email,
            'user_role'    => Auth::user()->role,
            'action'       => 'ip.blocked',
            'target_type'  => 'ip',
            'target_label' => $data['ip_address'],
            'ip_address'   => $request->ip(),
        ]);

        return back()->with('success', "IP {$data['ip_address']} has been blocked.");
    }

    // ── Security: unblock an IP ───────────────────────────────────────

    public function unblockIp(BlockedIp $blockedIp)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $ip = $blockedIp->ip_address;
        $blockedIp->delete();

        AuditLog::create([
            'user_id'      => Auth::id(),
            'user_name'    => Auth::user()->name,
            'user_email'   => Auth::user()->email,
            'user_role'    => Auth::user()->role,
            'action'       => 'ip.unblocked',
            'target_type'  => 'ip',
            'target_label' => $ip,
            'ip_address'   => request()->ip(),
        ]);

        return back()->with('success', "IP {$ip} has been unblocked.");
    }



    // ── Clear old logs ────────────────────────────────────────────────

    public function clear(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Only Super Admins can clear audit logs.');
        $days    = (int) $request->input('days', 30);
        $deleted = AuditLog::where('created_at', '<', now()->subDays($days))->delete();

        return back()->with('success', "Cleared {$deleted} audit log entries older than {$days} days.");
    }
}

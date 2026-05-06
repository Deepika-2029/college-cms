<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MediaFile;
use App\Models\Page;
use App\Models\TablePermission;
use App\Models\TablesRegistry;
use App\Models\Template;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct() {}

    public function index()
    {
        $user = Auth::user();
        $role = $user->role;

        $pageCount  = $this->countPages();
        $mediaCount = MediaFile::count();

        // Last login time for welcome banner
        $lastLoginLog = AuditLog::where('user_id', $user->id)
            ->where('action', 'login')
            ->latest()
            ->skip(1) // skip current session
            ->first();
        $lastLogin = $lastLoginLog ? $lastLoginLog->created_at->diffForHumans() : null;

        // Login/logout history for all roles
        $loginLogs = AuditLog::where('user_id', $user->id)
            ->whereIn('action', ['login', 'login.failed', 'logout'])
            ->latest()
            ->limit(8)
            ->get();

        if ($role === 'super_admin') {
            $stats = [
                'tables'     => TablesRegistry::count(),
                'media'      => $mediaCount,
                'pages'      => $pageCount,
                'users'      => User::count(),
                'templates'  => Template::count(),
                'published'  => Page::where('status', 'published')->count(),
                'drafts'     => Page::where('status', 'draft')->count(),
                'suspicious' => AuditLog::where('is_suspicious', true)->whereDate('created_at', today())->count(),
            ];
            $tables      = TablesRegistry::latest()->get();
            $plugins     = [];
            $recentAudit = AuditLog::with('user')->latest()->limit(8)->get();
            $recentUsers = User::latest()->limit(5)->get();
            $myLogs      = collect();

        } else {
            // Admin role — only show tables they can view
            $allowedPerms = TablePermission::where('user_id', $user->id)
                ->where('can_view', true)
                ->get()
                ->keyBy('table_name');

            $tables = TablesRegistry::latest()->get()
                ->filter(fn($t) => $allowedPerms->has($t->table_name))
                ->values();

            $plugins     = [];
            $recentAudit = collect();
            $recentUsers = collect();

            $myActionCounts = AuditLog::where('user_id', $user->id)
                ->select('action', DB::raw('count(*) as total'))
                ->groupBy('action')
                ->pluck('total', 'action')
                ->toArray();

            $stats = [
                'media'      => MediaFile::where('uploaded_by', $user->id)->count(),
                'pages'      => $pageCount,
                'published'  => Page::where('status', 'published')->count(),
                'drafts'     => Page::where('status', 'draft')->count(),
                'my_actions' => array_sum($myActionCounts),
                'my_tables'  => $tables->count(),
            ];

            $myLogs = AuditLog::where('user_id', $user->id)
                ->latest()
                ->limit(10)
                ->get();
        }

        return view('admin.dashboard.index', compact(
            'stats', 'tables', 'plugins', 'recentAudit', 'recentUsers',
            'role', 'myLogs', 'loginLogs', 'lastLogin'
        ));
    }

    private function countPages(): int
    {
        try {
            return Page::count() ?: $this->countPagesFromFiles();
        } catch (\Throwable) {
            return $this->countPagesFromFiles();
        }
    }

    private function countPagesFromFiles(): int
    {
        $dir = public_path('data/pages');
        if (! is_dir($dir)) return 0;
        return count(glob($dir . '/*.json'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserIpAllowlist;
use App\Models\TablePermission;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request)
    {
        $query = User::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->get('role')) {
            $query->where('role', $role);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status === 'active' ? 1 : 0);
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.form', ['user' => null]);
    }

    public function store(Request $request)
    {
        $this->authoriseRoleAssignment($request->input('role'));

        $data = $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'email'      => ['required', 'email', 'max:150', 'unique:users,email'],
            'password'   => ['required', 'string', 'min:10',
                'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[^a-zA-Z0-9]/', 'confirmed',
            ],
            'role'       => ['required', Rule::in(['super_admin', 'admin'])],
            'status'     => ['boolean'],
            'department' => ['nullable', 'string', 'max:150'],
            'bio'        => ['nullable', 'string', 'max:500'],
            'system_permissions'   => ['nullable', 'array'],
            'system_permissions.*' => ['string'],
        ]);

        if (! Auth::user()?->isSuperAdmin()) {
            unset($data['system_permissions']);
        }

        $data['password'] = Hash::make($data['password']);
        $data['status']   = $request->boolean('status', true);

        $user = User::create($data);

        if ($user->role === 'admin') {
            TablePermission::provisionForUser($user->id);
        }

        $this->audit->log('user.created', 'user', (string) $user->id, $user->email, null, [
            'name' => $user->name, 'email' => $user->email, 'role' => $user->role,
        ]);

        return redirect()->route('admin.users.index')
                         ->with('success', "User {$user->name} created successfully.");
    }

    public function edit(User $user)
    {
        $ipAllowlist = UserIpAllowlist::where('user_id', $user->id)->get();
        return view('admin.users.form', compact('user', 'ipAllowlist'));
    }

    public function update(Request $request, User $user)
    {
        $this->authoriseRoleAssignment($request->input('role'));

        if ($user->role === 'super_admin' && $request->input('role') !== 'super_admin') {
            $remaining = User::where('role', 'super_admin')->where('id', '!=', $user->id)->count();
            if ($remaining === 0) {
                return back()->with('error', 'Cannot downgrade the only Super Admin.');
            }
        }

        $rules = [
            'name'       => ['required', 'string', 'max:100'],
            'email'      => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
            'role'       => ['required', Rule::in(['super_admin', 'admin'])],
            'status'     => ['boolean'],
            'department' => ['nullable', 'string', 'max:150'],
            'bio'        => ['nullable', 'string', 'max:500'],
            'system_permissions'   => ['nullable', 'array'],
            'system_permissions.*' => ['string'],
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['string', 'min:10',
                'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[^a-zA-Z0-9]/', 'confirmed',
            ];
        }

        $data = $request->validate($rules);
        $old  = $user->only(['name', 'email', 'role', 'status']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if (! Auth::user()?->isSuperAdmin()) {
            unset($data['system_permissions']);
        } else {
            // Nullify if empty so it doesn't leave an empty array string in DB, or just array
            $data['system_permissions'] = $data['system_permissions'] ?? [];
        }

        $data['status'] = $request->boolean('status', true);
        $user->update($data);

        $this->audit->log('user.updated', 'user', (string) $user->id, $user->email, $old, [
            'name' => $user->name, 'email' => $user->email, 'role' => $user->role,
        ]);

        return redirect()->route('admin.users.index')
                         ->with('success', "User {$user->name} updated.");
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->role === 'super_admin' && User::where('role', 'super_admin')->count() <= 1) {
            return back()->with('error', 'Cannot delete the only Super Admin.');
        }

        $label = $user->email;
        $user->delete();

        $this->audit->log('user.deleted', 'user', null, $label);
        return back()->with('success', "User {$label} deleted.");
    }

    public function toggleStatus(User $user)
    {
        if ($user->id === Auth::id()) {
            return response()->json(['error' => 'Cannot deactivate yourself.'], 422);
        }

        $user->update(['status' => ! $user->status]);

        $this->audit->log(
            $user->status ? 'user.activated' : 'user.deactivated',
            'user', (string) $user->id, $user->email
        );

        return response()->json(['status' => $user->status]);
    }

    /**
     * Super admin resets another user's password (no old password needed).
     */
    public function resetPassword(Request $request, User $user)
    {
        // Only super_admin can reset other users' passwords
        abort_unless(Auth::user()->isSuperAdmin(), 403);

        // Cannot reset your own password here (use profile page)
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Use the Profile page to change your own password.');
        }

        $data = $request->validate([
            'new_password' => ['required', 'string', 'min:10',
                'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[^a-zA-Z0-9]/', 'confirmed',
            ],
        ], [
            'new_password.regex' => 'Password must have uppercase, digit, and special character.',
        ]);

        $user->update(['password' => Hash::make($data['new_password'])]);

        $this->audit->log('user.password_reset', 'user', (string) $user->id,
            $user->email . ' — reset by ' . Auth::user()->email);

        return back()->with('success', "Password for {$user->name} has been reset.");
    }

    // ── IP Allowlist management ───────────────────────────────────────────

    public function addIp(Request $request, User $user)
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);

        $data = $request->validate([
            'ip_address' => ['required', 'string', 'ip', 'max:45'],
            'label'      => ['nullable', 'string', 'max:100'],
        ]);

        UserIpAllowlist::firstOrCreate(
            ['user_id' => $user->id, 'ip_address' => $data['ip_address']],
            ['label'   => $data['label'] ?? null, 'added_by' => Auth::id()]
        );

        $this->audit->log('user.ip_allowlist.added', 'user', (string) $user->id,
            $user->email . ' — IP: ' . $data['ip_address']);

        return back()->with('success', "IP {$data['ip_address']} added to allowlist for {$user->name}.");
    }

    public function removeIp(User $user, UserIpAllowlist $ipEntry)
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);
        abort_unless($ipEntry->user_id === $user->id, 403);

        $ip = $ipEntry->ip_address;
        $ipEntry->delete();

        $this->audit->log('user.ip_allowlist.removed', 'user', (string) $user->id,
            $user->email . ' — IP: ' . $ip);

        return back()->with('success', "IP {$ip} removed from allowlist.");
    }

    // ── User own audit log (for admin role dashboard) ─────────────────────

    public function myLogs(Request $request)
    {
        $user = Auth::user();
        $logs = AuditLog::where('user_id', $user->id)
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.users.my-logs', compact('logs'));
    }

    private function authoriseRoleAssignment(?string $role): void
    {
        if (! Auth::user()?->isSuperAdmin()) {
            if ($role === 'super_admin') {
                abort(403, 'Only Super Admins can assign the Super Admin role.');
            }
        }
    }
}

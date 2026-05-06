<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * ProfileController
 *
 * Every logged-in user (super_admin + admin) can:
 *  - View their own profile
 *  - Update name, bio, department, avatar
 *  - Change their own password (requires current password verification)
 *  - Change their own email (requires current password verification)
 *
 * Role and status can NOT be changed from profile — only via User Management.
 */
class ProfileController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    // ── Show profile ──────────────────────────────────────────────────

    public function show()
    {
        $user        = Auth::user();
        $permissions = $user->permissions();
        $loginHistory = \App\Models\AuditLog::where('user_id', $user->id)
            ->whereIn('action', ['login', 'login.failed', 'logout'])
            ->latest()
            ->limit(12)
            ->get();
        return view('admin.profile.show', compact('user', 'permissions', 'loginHistory'));
    }

    // ── Update basic info (name, bio, department, avatar) ─────────────

    public function updateInfo(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'bio'        => ['nullable', 'string', 'max:500'],
            'department' => ['nullable', 'string', 'max:150'],
            'avatar'     => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:2048'],
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $file     = $request->file('avatar');
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $destDir  = public_path('media/avatars');

            if (! is_dir($destDir)) mkdir($destDir, 0755, true);

            // Remove old avatar file if it exists
            if ($user->avatar && file_exists(public_path($user->avatar))) {
                @unlink(public_path($user->avatar));
            }

            $file->move($destDir, $filename);
            $data['avatar'] = 'media/avatars/' . $filename;
        } elseif ($request->boolean('remove_avatar')) {
            if ($user->avatar && file_exists(public_path($user->avatar))) {
                @unlink(public_path($user->avatar));
            }
            $data['avatar'] = null;
        } else {
            unset($data['avatar']);
        }

        $old = $user->only(['name', 'bio', 'department', 'avatar']);
        $user->update($data);

        $this->audit->log('profile.updated', 'user', (string) $user->id, $user->email, $old, [
            'name' => $user->name, 'department' => $user->department,
        ]);

        return back()->with('success', 'Profile updated successfully.');
    }

    // ── Change email ──────────────────────────────────────────────────

    public function updateEmail(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'email'            => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
            'current_password' => ['required', 'string'],
        ]);

        // Require current password to change email
        if (! Hash::check($data['current_password'], $user->password)) {
            return back()
                ->withErrors(['current_password' => 'Current password is incorrect.'])
                ->withInput($request->only('email'));
        }

        $oldEmail = $user->email;
        $user->update(['email' => $data['email']]);

        $this->audit->log('profile.email_changed', 'user', (string) $user->id, $oldEmail, [
            'email' => $oldEmail,
        ], [
            'email' => $data['email'],
        ]);

        return back()->with('success', 'Email address updated successfully.');
    }

    // ── Change password ───────────────────────────────────────────────

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password'      => ['required', 'string'],
            'password'              => [
                'required', 'string', 'min:10', 'confirmed',
                'regex:/[A-Z]/',          // uppercase
                'regex:/[0-9]/',          // digit
                'regex:/[^a-zA-Z0-9]/',  // special char
                'different:current_password',
            ],
            'password_confirmation' => ['required'],
        ], [
            'password.different'    => 'New password must be different from your current password.',
            'password.regex'        => 'Password must contain uppercase, digit, and special character.',
        ]);

        // Verify current password
        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update(['password' => Hash::make($request->input('password'))]);

        $this->audit->log('profile.password_changed', 'user', (string) $user->id, $user->email);

        return back()->with('success_password', 'Password changed successfully. Please use your new password next time.');
    }
}

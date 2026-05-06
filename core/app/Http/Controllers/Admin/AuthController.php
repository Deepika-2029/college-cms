<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }
        return response()
            ->view('admin.auth.login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, private')
            ->header('Pragma', 'no-cache');
    }

    public function login(Request $request)
    {
        // Honeypot check — bots fill hidden fields, humans don't
        if ($request->filled('website')) {
            // Silently fail — don't tell the bot it was caught
            usleep(2000000);
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => 'The provided credentials do not match our records.',
            ]);
        }

        $credentials = $request->validate([
            'email'    => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:10', 'max:255'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // Regenerate session ID to prevent session fixation
            $request->session()->regenerate();

            // Clear any existing fingerprint so AdminAuthenticated sets a fresh one
            Session::forget('_admin_fp');
            // Record login timestamp for absolute session timeout
            Session::put('_admin_login_time', now()->timestamp);

            $user = Auth::user();

            // Reject if account is inactive (belt-and-suspenders — also checked in middleware)
            if (! $user->status) {
                Auth::logout();
                $request->session()->invalidate();
                return back()->withErrors(['email' => 'Your account has been deactivated.']);
            }

            $this->audit->log(
                'login',
                'auth',
                (string) $user->id,
                $user->email . ' — IP: ' . $request->ip()
            );

            return redirect()->intended(route('admin.dashboard'));
        }

        // Failed — LoginThrottle middleware also logs this, but we throw here
        $this->audit->log(
            'login.failed',
            'auth',
            null,
            $request->input('email') . ' — IP: ' . $request->ip()
        );

        // Always sleep a minimum 500ms to prevent timing attacks
        // (stops attackers from telling valid vs invalid email by response time)
        usleep(500000 + random_int(0, 200000));

        throw ValidationException::withMessages([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function logout(Request $request)
    {
        $this->audit->log('logout', 'auth', null, Auth::user()?->email);

        Auth::logout();

        // Fully invalidate session and regenerate CSRF token
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')
            ->with('success', 'You have been signed out securely.');
    }
}

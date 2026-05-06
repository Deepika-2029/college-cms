<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="robots" content="noindex, nofollow, noarchive" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login — {{ config('app.name','College CMS') }}</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect fill='%235a67d8' rx='20' width='100' height='100'/%3E%3C/svg%3E" />

    {{--
        Login page styles are inline — this page is unauthenticated so we
        cannot use the authenticated /admin/assets/* asset route.
        DO NOT link to any public CSS file here.
    --}}
    <style>
    @import url('/assets/css/fonts.css');

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    
    :root {
      --accent:     #6366f1; /* Indigo 500 */
      --accent-h:   #4f46e5;
      --accent-l:   #818cf8;
      --bg-base:    #020617; /* Slate 950 */
      --surface:    rgba(15, 23, 42, 0.6); /* Slate 900 with opacity for glass */
      --surface-border: rgba(255, 255, 255, 0.08);
      --text-main:  #f8fafc;
      --text-muted: #94a3b8;
      --red:        #ef4444;
      --green:      #10b981;
    }

    body {
      min-height: 100vh;
      font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
      font-size: 14px;
      -webkit-font-smoothing: antialiased;
      display: grid;
      place-items: center;
      background-color: var(--bg-base);
      background-image: 
        radial-gradient(circle at 15% 50%, rgba(99, 102, 241, 0.15), transparent 25%),
        radial-gradient(circle at 85% 30%, rgba(139, 92, 246, 0.15), transparent 25%);
      padding: 1.5rem;
      color: var(--text-main);
      overflow: hidden;
      position: relative;
    }

    /* Animated background glow */
    body::before {
      content: '';
      position: absolute;
      top: -50%; left: -50%; width: 200%; height: 200%;
      background: radial-gradient(circle 800px at 50% 50%, rgba(99, 102, 241, 0.08), transparent 100%);
      animation: rotateGlow 30s linear infinite;
      z-index: -1;
      pointer-events: none;
    }

    @keyframes rotateGlow {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    .login-wrap {
      width: 100%;
      max-width: 420px;
      display: flex;
      flex-direction: column;
      gap: 2rem;
      z-index: 10;
      animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Brand above card */
    .login-brand {
      display: flex;
      align-items: center;
      gap: 1rem;
      justify-content: center;
    }
    
    .login-brand .logo {
      width: 48px; height: 48px;
      background: linear-gradient(135deg, var(--accent-l), var(--accent-h));
      border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      font-weight: 800; color: white; font-size: 1.4rem;
      box-shadow: 0 8px 24px rgba(99, 102, 241, 0.4), inset 0 2px 4px rgba(255,255,255,0.3);
      flex-shrink: 0;
    }
    
    .login-brand-text {
      text-align: left;
    }
    .login-brand-text h1 { font-size: 1.35rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.03em; margin-bottom: 0.1rem; }
    .login-brand-text p  { font-size: 0.85rem; color: var(--text-muted); font-weight: 500; text-transform: uppercase; letter-spacing: 0.08em; }

    /* Card */
    .login-card {
      background: var(--surface);
      backdrop-filter: blur(20px) saturate(140%);
      -webkit-backdrop-filter: blur(20px) saturate(140%);
      border: 1px solid var(--surface-border);
      border-radius: 20px;
      padding: 2.5rem;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255,255,255,0.1);
    }

    .login-heading {
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--text-main);
      margin-bottom: 1.75rem;
      letter-spacing: -0.02em;
      text-align: center;
    }

    /* Alerts */
    .alert {
      padding: 0.85rem 1.1rem;
      border-radius: 12px;
      font-size: 0.88rem;
      margin-bottom: 1.5rem;
      display: flex; align-items: center; gap: 0.6rem;
      font-weight: 500;
      animation: alertPop 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes alertPop { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
    
    .alert-error   { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #fca5a5; }
    .alert-success { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #6ee7b7; }

    /* Form */
    .form-group { margin-bottom: 1.25rem; }
    label {
      display: block;
      font-size: 0.8rem;
      font-weight: 600;
      color: var(--text-muted);
      margin-bottom: 0.4rem;
      letter-spacing: 0.02em;
    }
    input[type="email"],
    input[type="password"],
    input[type="text"] {
      width: 100%;
      padding: 0.75rem 1rem;
      background: rgba(0, 0, 0, 0.2);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 10px;
      font-size: 0.95rem;
      font-family: inherit;
      color: var(--text-main);
      transition: all 0.2s ease;
      outline: none;
      box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
    }
    input:focus {
      background: rgba(0, 0, 0, 0.3);
      border-color: var(--accent);
      box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15), inset 0 2px 4px rgba(0,0,0,0.1);
    }
    input::placeholder { color: #475569; }
    input.is-invalid { border-color: rgba(239, 68, 68, 0.5); box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1); }
    .error-msg { font-size: 0.8rem; color: #fca5a5; margin-top: 0.4rem; }

    .pw-wrap { position: relative; }
    .pw-wrap input { padding-right: 3rem; }
    .pw-toggle {
      position: absolute; right: 0.5rem; top: 50%;
      transform: translateY(-50%);
      background: none; border: none; cursor: pointer;
      color: var(--text-muted); padding: 0.5rem;
      border-radius: 6px;
      display: flex; align-items: center; justify-content: center;
      transition: all 0.2s;
    }
    .pw-toggle:hover { color: var(--text-main); background: rgba(255,255,255,0.05); }

    .remember-row {
      display: flex; align-items: center; gap: 0.6rem;
      margin-bottom: 1.5rem; margin-top: 0.5rem;
      font-size: 0.85rem; color: var(--text-muted); cursor: pointer;
      width: fit-content;
    }
    .remember-row:hover { color: var(--text-main); }
    .remember-row input {
      width: 16px; height: 16px;
      accent-color: var(--accent);
      cursor: pointer;
      border-radius: 4px;
      background: rgba(0,0,0,0.2);
      border: 1px solid rgba(255,255,255,0.1);
    }

    .btn-login {
      width: 100%;
      padding: 0.85rem;
      background: linear-gradient(to right, var(--accent), var(--accent-h));
      color: white;
      border: none;
      border-radius: 10px;
      font-size: 0.95rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex; align-items: center; justify-content: center; gap: 0.5rem;
      letter-spacing: 0.02em;
      box-shadow: 0 8px 16px rgba(99, 102, 241, 0.25), inset 0 1px 1px rgba(255,255,255,0.2);
    }
    .btn-login:hover { transform: translateY(-2px); box-shadow: 0 12px 20px rgba(99, 102, 241, 0.3), inset 0 1px 1px rgba(255,255,255,0.2); filter: brightness(1.1); }
    .btn-login:active { transform: translateY(0); box-shadow: 0 4px 8px rgba(99, 102, 241, 0.2); }
    .btn-login:disabled { opacity: 0.6; cursor: not-allowed; transform: none; filter: grayscale(50%); }

    /* Divider */
    .login-footer-note {
      text-align: center;
      font-size: 0.75rem;
      color: #475569;
      padding-top: 1.5rem;
      border-top: 1px solid rgba(255,255,255,0.05);
      margin-top: 1.75rem;
      display: flex; align-items: center; justify-content: center; gap: 0.4rem;
      font-weight: 500;
    }

    @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="login-wrap">
    <div class="login-brand">
        <div class="logo">{{ strtoupper(substr(config('app.name','C'),0,1)) }}</div>
        <div class="login-brand-text">
            <h1>{{ config('app.name','College CMS') }}</h1>
            <p>Administration</p>
        </div>
    </div>

    <div class="login-card">
        <div class="login-heading">Sign in to your account</div>

        @if($errors->any())
        <div class="alert alert-error">
            <div>{{ $errors->first() }}</div>
        </div>
        @endif

        @if(session('success'))
        <div class="alert alert-success">
            <div>{{ session('success') }}</div>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-error">
            <div>{{ session('error') }}</div>
        </div>
        @endif

        @if(request('setup') === 'done')
        <div class="alert alert-success">
            <div><strong>Setup complete!</strong> Log in with your configured credentials.</div>
        </div>
        @endif

        <form action="{{ route('admin.login') }}" method="POST" id="login-form">
            @csrf

            {{-- Honeypot — bots fill this, humans don't see it --}}
            <div style="position:absolute;left:-9999px;top:-9999px;height:0;overflow:hidden;" aria-hidden="true">
                <input type="text" name="website" tabindex="-1" autocomplete="off" value="" aria-hidden="true" />
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    class="{{ $errors->has('email') ? 'is-invalid' : '' }}"
                    value="{{ old('email') }}"
                    required
                    autocomplete="email"
                    autofocus
                    placeholder="you@college.edu"
                />
                @error('email')<div class="error-msg">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="pw-wrap">
                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="{{ $errors->has('password') ? 'is-invalid' : '' }}"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••••••"
                    />
                    <button type="button" class="pw-toggle" title="Toggle password visibility">
                        <span id="pw-eye" class="pw-eye-icon">
                            <svg id="icon-eye" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg id="icon-eye-slash" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </span>
                    </button>
                </div>
                @error('password')<div class="error-msg">{{ $message }}</div>@enderror
            </div>

            <label class="remember-row">
                <input type="checkbox" name="remember" id="remember" />
                Keep me signed in
            </label>

            <button type="submit" class="btn-login" id="login-btn">
                <span>Sign In</span>
            </button>

            <div class="login-footer-note">
                Protected by rate limiting &amp; IP monitoring
            </div>
        </form>
    </div>
</div>

<style>
.spin-icon{display:inline-flex;animation:spin .7s linear infinite;margin-right:6px}
@keyframes spin{to{transform:rotate(360deg)}}
.pw-eye-icon{display:inline-flex;align-items:center;color:inherit}
</style>
<script>
(function() {
    const pwBtn       = document.querySelector('.pw-toggle');
    const pwInp       = document.getElementById('password');
    const iconEye     = document.getElementById('icon-eye');
    const iconSlash   = document.getElementById('icon-eye-slash');
    if (pwBtn && pwInp) {
        pwBtn.addEventListener('click', function() {
            const isPass = pwInp.type === 'password';
            pwInp.type = isPass ? 'text' : 'password';
            if (iconEye)   iconEye.style.display   = isPass ? 'none' : '';
            if (iconSlash) iconSlash.style.display = isPass ? ''     : 'none';
        });
    }
    const form = document.getElementById('login-form');
    const btn  = document.getElementById('login-btn');
    if (form && btn) {
        form.addEventListener('submit', function() {
            btn.disabled = true;
            btn.innerHTML = '<span class="spin-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg></span><span>Signing in…</span>';
        });
    }
})();
</script>
</body>
</html>

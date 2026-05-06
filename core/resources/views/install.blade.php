<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Your CMS — Installation Wizard</title>
    <link href="/admin-vendor/fonts/outfit/outfit.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-light: #818cf8;
            --bg: #0f172a;
            --surface: rgba(30, 41, 59, 0.7);
            --border: rgba(255, 255, 255, 0.1);
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --error: #ef4444;
            --success: #10b981;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--bg);
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(139, 92, 246, 0.15) 0px, transparent 50%);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .container {
            width: 100%;
            max-width: 600px;
            background: var(--surface);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 3rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .logo {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 16px;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            color: white;
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
        }

        h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        p.subtitle {
            color: var(--text-muted);
            font-size: 1rem;
        }

        .section-title {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            font-weight: 600;
        }

        .section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
            margin-left: 1rem;
        }

        .grid-requirements {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .requirement-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
            color: var(--text-muted);
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        .requirement-item.valid {
            color: var(--text);
        }

        .icon-small {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text);
        }

        input {
            width: 100%;
            padding: 0.875rem 1.25rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            transition: all 0.2s;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 2rem;
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.2);
        }

        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(99, 102, 241, 0.3);
        }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            filter: grayscale(1);
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 640px) {
            .container { padding: 2rem; }
            .grid-requirements, .row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <div class="logo">C</div>
            <h1>System Installation</h1>
            <p class="subtitle">Complete the information below to set up your website.</p>
        </div>

        @if(session('error'))
        <div class="alert alert-error">
            {{ session('error') }}
        </div>
        @endif

        <div class="section-title">Step 1: Check Requirements</div>
        <div class="grid-requirements">
            @foreach($requirements as $ext => $valid)
            <div class="requirement-item {{ $valid ? 'valid' : '' }}">
                <svg class="icon-small" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    @if($valid)
                    <polyline points="20 6 9 17 4 12" stroke="{{ $valid ? 'var(--success)' : 'var(--error)' }}"></polyline>
                    @else
                    <line x1="18" y1="6" x2="6" y2="18" stroke="var(--error)"></line>
                    <line x1="6" y1="6" x2="18" y2="18" stroke="var(--error)"></line>
                    @endif
                </svg>
                {{ strtoupper($ext) }} {{ $valid ? 'Available' : 'Missing' }}
            </div>
            @endforeach
            <div class="requirement-item {{ $envWritable ? 'valid' : '' }}">
                <svg class="icon-small" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <polyline points="20 6 9 17 4 12" stroke="{{ $envWritable ? 'var(--success)' : 'var(--error)' }}"></polyline>
                </svg>
                .env Writable
            </div>
            <div class="requirement-item {{ $storageWritable ? 'valid' : '' }}">
                <svg class="icon-small" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <polyline points="20 6 9 17 4 12" stroke="{{ $storageWritable ? 'var(--success)' : 'var(--error)' }}"></polyline>
                </svg>
                Storage Writable
            </div>
        </div>

        <form action="{{ route('install.process') }}" method="POST">
            @csrf
            
            <div class="section-title">Step 2: Database Configuration</div>
            <div class="row">
                <div class="form-group">
                    <label>Database Host</label>
                    <input type="text" name="db_host" value="{{ old('db_host', '127.0.0.1') }}" required>
                </div>
                <div class="form-group">
                    <label>Database Port</label>
                    <input type="text" name="db_port" value="{{ old('db_port', '3306') }}" required>
                </div>
            </div>
            <div class="form-group">
                <label>Database Name</label>
                <input type="text" name="db_name" value="{{ old('db_name') }}" placeholder="e.g. college_cms" required>
            </div>
            <div class="row">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="db_user" value="{{ old('db_user') }}" placeholder="Database User" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="db_pass" placeholder="Database Password (optional)">
                </div>
            </div>

            <div class="section-title" style="margin-top: 2rem;">Step 3: Admin Account</div>
            <div class="form-group">
                <label>Administrator Name</label>
                <input type="text" name="admin_name" value="{{ old('admin_name') }}" placeholder="Full Name" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="admin_email" value="{{ old('admin_email') }}" placeholder="admin@example.com" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="admin_password" placeholder="Min. 8 characters" required>
            </div>

            <button type="submit" class="btn-submit" {{ $ready ? '' : 'disabled' }}>
                {{ $ready ? 'Initialize System' : 'Fix Requirements first' }}
            </button>
        </form>
    </div>

</body>
</html>

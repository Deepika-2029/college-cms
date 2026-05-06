<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>404 — Page Not Found</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1e293b;
        }
        .wrap {
            text-align: center;
            padding: 2rem;
            max-width: 480px;
        }
        .code {
            font-size: 6rem;
            font-weight: 800;
            color: #e2e8f0;
            line-height: 1;
            margin-bottom: 1rem;
            letter-spacing: -0.05em;
        }
        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        p {
            color: #64748b;
            line-height: 1.65;
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }
        .links { display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; }
        a {
            display: inline-block;
            padding: 0.6rem 1.25rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: opacity 0.15s;
        }
        a:hover { opacity: 0.85; }
        .btn-home   { background: #6366f1; color: white; }
        .btn-admin  { background: white; color: #6366f1; border: 1.5px solid #6366f1; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="code">404</div>
        <h1>Page not found</h1>
        <p>
            The page you're looking for doesn't exist or hasn't been published yet.
            @if(isset($exception) && $exception->getMessage() && app()->environment('local'))
                <br><small style="color:#94a3b8;font-size:0.8rem;">{{ $exception->getMessage() }}</small>
            @endif
        </p>
        <div class="links">
            <a href="/" class="btn-home">← Go home</a>
            <a href="/admin" class="btn-admin">Admin panel</a>
        </div>
    </div>
</body>
</html>

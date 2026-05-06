<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckInstallation
{
    public function handle(Request $request, Closure $next)
    {
        $installLock = defined('CMS_SYSTEM_ROOT') 
            ? CMS_SYSTEM_ROOT . '/install.lock' 
            : base_path('install.lock');
            
        $isInstalled = file_exists($installLock);

        if ($request->is('install*')) {
            if ($isInstalled) {
                return redirect('/');
            }
            return $next($request);
        }

        if (!$isInstalled) {
            return redirect('/install');
        }

        return $next($request);
    }
}

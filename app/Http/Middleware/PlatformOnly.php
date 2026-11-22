<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PlatformOnly
{
    public function handle(Request $request, Closure $next)
    {
        if (! auth('platform')->check()) {
            return redirect()->route('platform.login');
        }

        return $next($request);
    }
}

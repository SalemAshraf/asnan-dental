<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** An expired, unactivated trial can read its data but nothing can be added, changed or removed. Enforced on the request, not on buttons. */
class EnsureClinicWritable
{
    public function handle(Request $request, Closure $next)
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }
        if (Auth::guard('web')->user()?->clinic?->isReadOnly()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'read_only'], 403);
            }

            return back()->with('error', 'read_only');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Support\Tenant;
use Closure;
use Illuminate\Http\Request;

/** Token-authenticated API requests: same active/tenant rules as the web app. */
class ApiClinicContext
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user === null || ! $user->is_active || $user->clinic?->suspended_at !== null) {
            $user?->currentAccessToken()?->delete();

            return response()->json(['message' => 'account_unavailable'], 403);
        }

        Tenant::set($user->clinic_id);

        try {
            return $next($request);
        } finally {
            Tenant::set(null);
        }
    }
}

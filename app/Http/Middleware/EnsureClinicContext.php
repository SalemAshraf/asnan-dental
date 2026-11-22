<?php

namespace App\Http\Middleware;

use App\Support\Tenant;
use Closure;
use Illuminate\Http\Request;

class EnsureClinicContext
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user !== null && $user->clinic?->suspended_at !== null) {
            auth()->logout();
            $request->session()?->invalidate();

            return redirect()->route('login')->with('error', 'clinic_suspended');
        }

        if ($user === null || ! $user->is_active) {
            auth()->logout();
            $request->session()?->invalidate();

            return redirect()->route('login');
        }

        Tenant::set($user->clinic_id);
        inertia()->share('auth.user', [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
            'clinic_id' => $user->clinic_id,
            'branch_id' => $user->branch_id,
        ]);

        try {
            return $next($request);
        } finally {
            Tenant::set(null);
        }
    }
}

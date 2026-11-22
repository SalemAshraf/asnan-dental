<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth.user' => fn () => $request->user() ? [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'role' => $request->user()->role,
                'clinic_id' => $request->user()->clinic_id,
                'branch_id' => $request->user()->branch_id,
            ] : null,
            'auth.clinic' => fn () => $request->user()?->clinic?->only(['name', 'currency', 'timezone']),
            'auth.subscription' => fn () => ($sub = $request->user()?->clinic?->subscription) ? [
                'status' => $sub->status,
                'trial_ends_at' => $sub->trial_ends_at?->toIso8601String(),
                'days_left' => $sub->trialExpired() ? 0 : ($sub->trial_ends_at ? max(0, (int) ceil(now()->floatDiffInDays($sub->trial_ends_at, false))) : null),
                'read_only' => $sub->trialExpired(),
            ] : null,
            'platform' => fn () => ['sales_whatsapp' => config('platform.sales_whatsapp')],
            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }
}

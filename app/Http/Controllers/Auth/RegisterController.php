<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'plan' => Plan::where('is_active', true)->where('is_public', true)->where('slug', (string) request('plan'))->first(['slug', 'name_ar', 'name_en', 'trial_days']),
            'trialDays' => (int) config('platform.trial_days', 14),
        ]);
    }

    public function store(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'clinic_name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'plan' => 'nullable|string|max:40',
        ]);

        // Every NEW clinic gets a free trial (config platform.trial_days). A plan picked on the pricing page is attached
        // (its limits apply during the trial); with no pick the trial has no plan and no limits. No payment is taken. When the
        // trial date passes without a manual activation from the platform, the clinic becomes read-only (see Clinic::isReadOnly).
        $clinic = Clinic::create(['name' => $request->clinic_name]);
        $plan = $request->filled('plan') ? Plan::where('is_active', true)->where('is_public', true)->where('slug', $request->plan)->first() : null;
        Subscription::create([
            'clinic_id' => $clinic->id,
            'plan_id' => $plan?->id,
            'status' => 'trialing',
            'trial_enforced' => true,
            'trial_ends_at' => now()->addDays((int) config('platform.trial_days', 14)),
        ]);
        $user = User::create([
            'clinic_id' => $clinic->id,
            'role' => 'owner',
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password, // hashed by the User model cast
            'is_active' => true,
        ]);

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}

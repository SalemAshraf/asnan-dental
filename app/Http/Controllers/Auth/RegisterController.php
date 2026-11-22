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
            'trialDays' => config('platform.trial_days'),
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

        // Trial is a commercial term the owner has not set. A NEW clinic gets a trial only when
        // PLATFORM_TRIAL_DAYS is configured; otherwise its subscription is pending manual activation
        // and the account is read-only until the platform activates it. No payment is taken either way.
        $clinic = Clinic::create(['name' => $request->clinic_name]);
        $plan = $request->filled('plan') ? Plan::where('is_active', true)->where('is_public', true)->where('slug', $request->plan)->first() : null;
        $trialDays = config('platform.trial_days');
        Subscription::create([
            'clinic_id' => $clinic->id,
            'plan_id' => $plan?->id,
            'status' => $trialDays ? 'trialing' : 'pending',
            'trial_enforced' => (bool) $trialDays,
            'trial_ends_at' => $trialDays ? now()->addDays((int) $trialDays) : null,
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

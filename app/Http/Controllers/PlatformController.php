<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Plan;
use App\Models\SalesInquiry;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PlatformLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/** Platform staff panel. Aggregates only: it never shows a clinic's patients, phones or clinical data. */
class PlatformController extends Controller
{
    public function loginForm()
    {
        return Inertia::render('Platform/Login');
    }

    public function login(Request $r)
    {
        $d = $r->validate(['email' => 'required|email', 'password' => 'required|string']);
        $key = 'platform-login:'.strtolower($d['email']).'|'.$r->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many attempts. Try again later.']);
        }
        if (! Auth::guard('platform')->attempt($d, $r->boolean('remember'))) {
            RateLimiter::hit($key);
            throw ValidationException::withMessages(['email' => 'These credentials do not match.']);
        }
        RateLimiter::clear($key);
        $r->session()->regenerate();
        PlatformLog::record('platform.login');

        return redirect()->route('platform.dashboard');
    }

    public function logout(Request $r)
    {
        PlatformLog::record('platform.logout');
        Auth::guard('platform')->logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect()->route('platform.login');
    }

    public function dashboard()
    {
        $subs = Subscription::query()->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');
        $trialEnding = Subscription::with('clinic:id,name')->where('status', 'trialing')->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now()->addDays(3))->orderBy('trial_ends_at')->limit(20)->get()
            ->map(fn ($s) => ['id' => $s->clinic_id, 'name' => $s->clinic?->name, 'trial_ends_at' => $s->trial_ends_at?->toDateString()]);
        $newInquiries = SalesInquiry::where('status', 'new')->count();

        return Inertia::render('Platform/Dashboard', [
            'counts' => [
                'clinics' => Clinic::count(),
                'trialing' => (int) ($subs['trialing'] ?? 0),
                'active' => (int) ($subs['active'] ?? 0),
                'past_due' => (int) ($subs['past_due'] ?? 0),
                'cancelled' => (int) ($subs['cancelled'] ?? 0),
                'new_inquiries' => $newInquiries,
            ],
            'trialEnding' => $trialEnding,
        ]);
    }

    public function clinics()
    {
        $rows = Clinic::with('subscription.plan')->orderByDesc('id')->get()->map(function (Clinic $c) {
            $sub = $c->subscription;

            return [
                'id' => $c->id,
                'name' => $c->name,
                'created_at' => $c->created_at?->toDateString(),
                'suspended' => $c->suspended_at !== null,
                'users' => User::where('clinic_id', $c->id)->count(),
                'patients' => Patient::where('clinic_id', $c->id)->count(),
                'subscription' => $sub ? [
                    'status' => $sub->status,
                    'trial_ends_at' => $sub->trial_ends_at?->toDateString(),
                    'plan' => $sub->plan ? ['ar' => $sub->plan->name_ar, 'en' => $sub->plan->name_en] : null,
                ] : null,
            ];
        });

        return Inertia::render('Platform/Clinics', ['clinics' => $rows]);
    }

    public function clinic(Clinic $clinic)
    {
        $sub = $clinic->subscription;

        return Inertia::render('Platform/Clinic', [
            'clinic' => [
                'id' => $clinic->id,
                'name' => $clinic->name,
                'created_at' => $clinic->created_at?->toDateString(),
                'suspended' => $clinic->suspended_at !== null,
                'users' => User::where('clinic_id', $clinic->id)->count(),
                'patients' => Patient::where('clinic_id', $clinic->id)->count(),
                'appointments' => Appointment::where('clinic_id', $clinic->id)->count(),
            ],
            'subscription' => $sub ? [
                'status' => $sub->status,
                'trial_ends_at' => $sub->trial_ends_at?->toDateString(),
                'current_period_end' => $sub->current_period_end?->toDateString(),
                'note' => $sub->note,
                'plan_id' => $sub->plan_id,
            ] : null,
            'plans' => Plan::where('is_active', true)->orderBy('sort')->get(['id', 'slug', 'name_ar', 'name_en']),
        ]);
    }

    public function suspend(Clinic $clinic)
    {
        $clinic->forceFill(['suspended_at' => now()])->save();
        PlatformLog::record('clinic.suspend', $clinic->id);

        return back()->with('success', 'ok');
    }

    public function activate(Clinic $clinic)
    {
        $clinic->forceFill(['suspended_at' => null])->save();
        PlatformLog::record('clinic.activate', $clinic->id);

        return back()->with('success', 'ok');
    }

    public function subscribe(Request $r, Clinic $clinic)
    {
        $d = $r->validate([
            'plan_id' => 'nullable|exists:plans,id',
            'status' => 'required|in:trialing,active,past_due,cancelled',
            'current_period_end' => 'nullable|date',
            'note' => 'nullable|string|max:1000',
        ]);
        $sub = $clinic->subscription ?: new Subscription(['clinic_id' => $clinic->id]);
        // Manual activation clears the trial rule: an active subscription is never read-only.
        $sub->fill([
            'plan_id' => $d['plan_id'] ?? null,
            'status' => $d['status'],
            'current_period_end' => $d['current_period_end'] ?? null,
            'note' => $d['note'] ?? $sub->note,
        ]);
        if ($d['status'] === 'active') {
            $sub->trial_enforced = false;
        }
        $sub->save();
        PlatformLog::record('clinic.subscribe', $clinic->id, ['status' => $d['status'], 'plan_id' => $d['plan_id'] ?? null]);

        return back()->with('success', 'ok');
    }

    public function plans()
    {
        return Inertia::render('Platform/Plans', [
            'plans' => Plan::orderBy('sort')->orderBy('id')->get(),
        ]);
    }

    public function savePlan(Request $r, ?Plan $plan = null)
    {
        $d = $r->validate([
            'slug' => 'required|string|max:40|regex:/^[a-z0-9-]+$/|unique:plans,slug,'.($plan?->id ?? 'NULL').',id',
            'name_ar' => 'required|string|max:120',
            'name_en' => 'required|string|max:120',
            'blurb_ar' => 'nullable|string|max:500',
            'blurb_en' => 'nullable|string|max:500',
            'price' => 'nullable|numeric|min:0',
            'price_yearly' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'period' => 'nullable|in:monthly,yearly',
            'trial_days' => 'nullable|integer|min:0|max:365',
            'max_users' => 'nullable|integer|min:0',
            'max_patients' => 'nullable|integer|min:0',
            'features_ar' => 'nullable|array',
            'features_ar.*' => 'string|max:200',
            'features_en' => 'nullable|array',
            'features_en.*' => 'string|max:200',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
            'signup_default' => 'boolean',
            'sort' => 'integer|min:0|max:9999',
        ]);
        $plan = $plan ?: new Plan;
        $plan->fill($d)->save();
        PlatformLog::record($plan->wasRecentlyCreated ? 'plan.create' : 'plan.update', null, ['slug' => $plan->slug]);

        return back()->with('success', 'ok');
    }

    public function inquiries()
    {
        return Inertia::render('Platform/Inquiries', [
            'inquiries' => SalesInquiry::orderByDesc('id')->limit(200)->get(['id', 'name', 'clinic_name', 'phone', 'email', 'plan_slug', 'message', 'status', 'created_at']),
        ]);
    }

    public function inquiryStatus(Request $r, SalesInquiry $inquiry)
    {
        $d = $r->validate(['status' => 'required|in:new,contacted,closed']);
        $inquiry->forceFill(['status' => $d['status']])->save();
        PlatformLog::record('inquiry.status', null, ['id' => $inquiry->id, 'status' => $d['status']]);

        return back()->with('success', 'ok');
    }
}

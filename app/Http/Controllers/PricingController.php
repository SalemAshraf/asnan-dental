<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\SalesInquiry;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PricingController extends Controller
{
    public function show()
    {
        return Inertia::render('Pricing', [
            'plans' => Plan::where('is_active', true)->where('is_public', true)->orderBy('sort')->orderBy('id')
                ->get(['slug', 'name_ar', 'name_en', 'blurb_ar', 'blurb_en', 'price', 'price_yearly', 'currency', 'period', 'trial_days', 'features_ar', 'features_en', 'max_users', 'max_patients']),
            'trialDays' => (int) config('platform.trial_days', 14),
        ]);
    }

    public function inquiry(Request $r)
    {
        if ($r->filled('website')) { // honeypot
            return back()->with('success', 'ok');
        }
        $d = $r->validate([
            'name' => 'required|string|max:120',
            'clinic_name' => 'nullable|string|max:160',
            'phone' => 'nullable|string|max:40|required_without:email',
            'email' => 'nullable|email|max:190|required_without:phone',
            'plan_slug' => 'nullable|string|max:40',
            'message' => 'nullable|string|max:1500',
        ]);
        SalesInquiry::create($d);

        return back()->with('success', 'ok');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\TreatmentPlan;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $tz = auth()->user()->clinic?->timezone ?? config('app.timezone');
        $todayStart = now($tz)->startOfDay()->utc();
        $todayEnd = now($tz)->endOfDay()->utc();

        $todayAppointments = Appointment::with(['patient:id,full_name,phone', 'dentist:id,name', 'branch:id,name'])
            ->whereBetween('starts_at', [$todayStart, $todayEnd])
            ->orderBy('starts_at')
            ->limit(20)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => [
                'today_appointments' => Appointment::whereBetween('starts_at', [$todayStart, $todayEnd])
                    ->whereIn('status', Appointment::ACTIVE_STATUSES)->count(),
                'patients_total' => Patient::count(),
                'open_plans' => TreatmentPlan::whereIn('status', ['proposed', 'accepted', 'in_progress'])->count(),
                'outstanding_minor' => (int) Invoice::whereIn('status', ['issued', 'partially_paid'])
                    ->selectRaw('COALESCE(SUM(total_minor - paid_minor),0) as b')->value('b'),
                'collected_month_minor' => (int) \App\Models\Payment::whereBetween('paid_at', [now($tz)->startOfMonth()->utc(), now($tz)->endOfMonth()->utc()])
                    ->sum('amount_minor'),
            ],
            'todayAppointments' => $todayAppointments,
            'clinic' => auth()->user()->clinic?->only(['name', 'country', 'currency', 'timezone']),
        ]);
    }
}

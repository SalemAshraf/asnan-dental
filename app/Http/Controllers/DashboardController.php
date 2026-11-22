<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\TreatmentPlan;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $tz = auth()->user()->clinic?->timezone ?? config('app.timezone');
        $now = now($tz);
        $todayStart = $now->copy()->startOfDay()->utc();
        $todayEnd = $now->copy()->endOfDay()->utc();

        $todayAppointments = Appointment::with(['patient:id,full_name,phone', 'dentist:id,name', 'branch:id,name'])
            ->whereBetween('starts_at', [$todayStart, $todayEnd])
            ->orderBy('starts_at')
            ->limit(30)
            ->get();

        // Appointments per clinic-local day, last 14 days (KPI sparkline).
        $apptFrom = $now->copy()->subDays(13)->startOfDay();
        $apptSeries = array_fill(0, 14, 0);
        Appointment::where('starts_at', '>=', $apptFrom->copy()->utc())
            ->whereIn('status', Appointment::ACTIVE_STATUSES)
            ->get(['starts_at'])
            ->each(function ($a) use ($apptFrom, &$apptSeries, $tz) {
                $i = $apptFrom->copy()->startOfDay()->diffInDays($a->starts_at->timezone($tz)->copy()->startOfDay(), false);
                if ($i >= 0 && $i < 14) {
                    $apptSeries[$i]++;
                }
            });

        // Last 6 clinic-local months: collected payments, issued invoice totals, new patients.
        $monthStart = $now->copy()->subMonths(5)->startOfMonth();
        $monthKeys = collect(range(0, 5))->map(fn ($i) => $monthStart->copy()->addMonths($i)->format('Y-m'));

        $monthly = $monthKeys->mapWithKeys(fn ($k) => [$k => ['collected' => 0, 'invoiced' => 0, 'patients' => 0]])->toArray();

        Payment::where('paid_at', '>=', $monthStart->copy()->utc())->get(['paid_at', 'amount_minor'])
            ->each(function ($p) use (&$monthly, $tz) {
                $k = $p->paid_at->timezone($tz)->format('Y-m');
                if (isset($monthly[$k])) {
                    $monthly[$k]['collected'] += $p->amount_minor;
                }
            });

        Invoice::where('created_at', '>=', $monthStart->copy()->utc())
            ->whereIn('status', ['issued', 'partially_paid', 'paid'])
            ->get(['created_at', 'total_minor'])
            ->each(function ($inv) use (&$monthly, $tz) {
                $k = $inv->created_at->timezone($tz)->format('Y-m');
                if (isset($monthly[$k])) {
                    $monthly[$k]['invoiced'] += $inv->total_minor;
                }
            });

        Patient::where('created_at', '>=', $monthStart->copy()->utc())->get(['created_at'])
            ->each(function ($p) use (&$monthly, $tz) {
                $k = $p->created_at->timezone($tz)->format('Y-m');
                if (isset($monthly[$k])) {
                    $monthly[$k]['patients']++;
                }
            });

        $chart = $monthKeys->map(fn ($k) => [
            'label' => substr($k, 2, 2) . '/' . substr($k, 5),
            'a' => $monthly[$k]['collected'],
            'b' => $monthly[$k]['invoiced'],
        ])->values();

        $unpaid = Invoice::with('patient:id,full_name,file_no')
            ->whereIn('status', ['issued', 'partially_paid'])
            ->orderByRaw('(total_minor - paid_minor) DESC')
            ->limit(5)
            ->get(['id', 'patient_id', 'number', 'status', 'total_minor', 'paid_minor', 'currency']);

        return Inertia::render('Dashboard', [
            'stats' => [
                // All of today's appointments; the hint breaks down active vs finished
                // so the number always matches the visible schedule list.
                'today_appointments' => Appointment::whereBetween('starts_at', [$todayStart, $todayEnd])->count(),
                'today_active' => Appointment::whereBetween('starts_at', [$todayStart, $todayEnd])
                    ->whereIn('status', Appointment::ACTIVE_STATUSES)->count(),
                'today_done' => Appointment::whereBetween('starts_at', [$todayStart, $todayEnd])
                    ->where('status', 'completed')->count(),
                'week_appointments' => Appointment::whereBetween('starts_at', [$todayStart, $now->copy()->addDays(7)->endOfDay()->utc()])
                    ->whereIn('status', Appointment::ACTIVE_STATUSES)->count(),
                'patients_total' => Patient::count(),
                'new_patients_month' => Patient::where('created_at', '>=', $now->copy()->startOfMonth()->utc())->count(),
                'open_plans' => TreatmentPlan::whereIn('status', ['proposed', 'accepted', 'in_progress'])->count(),
                'outstanding_minor' => (int) Invoice::whereIn('status', ['issued', 'partially_paid'])
                    ->selectRaw('COALESCE(SUM(total_minor - paid_minor),0) as b')->value('b'),
                'collected_month_minor' => (int) $monthly[$now->format('Y-m')]['collected'],
            ],
            'apptSeries' => array_values($apptSeries),
            'collectedSeries' => $monthKeys->map(fn ($k) => $monthly[$k]['collected'])->values(),
            'patientsSeries' => $monthKeys->map(fn ($k) => $monthly[$k]['patients'])->values(),
            'chart' => $chart,
            'todayAppointments' => $todayAppointments,
            'unpaid' => $unpaid,
            'todayLabel' => $now->translatedFormat('l j F Y'),
            'clinic' => auth()->user()->clinic?->only(['name', 'country', 'currency', 'timezone']),
        ]);
    }
}

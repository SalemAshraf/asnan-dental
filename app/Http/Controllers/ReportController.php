<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\TreatmentPlanItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless($request->user()->isOwner(), 403);

        return Inertia::render('Reports', $this->reportData($request));
    }

    /** CSV export of the same owner-only report, same range. */
    public function export(Request $request)
    {
        abort_unless($request->user()->isOwner(), 403);
        $d = $this->reportData($request);
        $currency = $d['currency'];
        $money = fn (int $minor) => number_format($minor / 100, 2, '.', '');

        $rows = [];
        $rows[] = ['section', 'label', 'value', 'currency'];
        $rows[] = ['range', 'from', $d['range']['from'], ''];
        $rows[] = ['range', 'to', $d['range']['to'], ''];
        foreach ([
            'collected' => $d['cards']['collected_minor'],
            'invoiced' => $d['cards']['invoiced_minor'],
            'outstanding' => $d['cards']['outstanding_minor'],
        ] as $k => $v) {
            $rows[] = ['summary', $k, $money($v), $currency];
        }
        $rows[] = ['summary', 'new_patients', $d['cards']['new_patients'], ''];
        $rows[] = ['summary', 'appointments', $d['cards']['appointments'], ''];
        $rows[] = ['summary', 'completion_pct', $d['cards']['completion_pct'], ''];
        $rows[] = ['summary', 'no_show_pct', $d['cards']['no_show_pct'], ''];
        foreach ($d['byMethod'] as $m => $v) {
            $rows[] = ['payments_by_method', $m, $money($v), $currency];
        }
        foreach ($d['byStatus'] as $st => $v) {
            $rows[] = ['appointments_by_status', $st, $v, ''];
        }
        foreach ($d['perDentist'] as $r) {
            $rows[] = ['per_dentist', $r['dentist'], "total={$r['total']};completed={$r['completed']};no_show={$r['no_show']};cancelled={$r['cancelled']}", ''];
        }
        foreach ($d['topServices'] as $r) {
            $rows[] = ['top_services', $r['name'], "qty={$r['count']};total=".$money($r['total_minor']), $currency];
        }
        foreach ($d['commissions'] as $r) {
            $rows[] = ['commission', $r['dentist'], "rate=".($r['rate_bp'] / 100)."%;base=".$money($r['base_minor']).";commission=".$money($r['commission_minor']), $currency];
        }

        $filename = 'taqwim-report-'.$d['range']['from'].'-to-'.$d['range']['to'].'.csv';

        // Spreadsheet formula injection: a text cell starting with =, +, -, @ (or a tab/CR)
        // executes as a formula in Excel. Prefix such cells with a single quote.
        $safe = fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v;

        return response()->streamDownload(function () use ($rows, $safe) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads Arabic correctly
            foreach ($rows as $row) {
                fputcsv($out, array_map($safe, $row));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function reportData(Request $request): array
    {
        $tz = $request->user()->clinic?->timezone ?? config('app.timezone');
        $from = Carbon::parse($request->input('from', now($tz)->subDays(89)->toDateString()), $tz)->startOfDay();
        $to = Carbon::parse($request->input('to', now($tz)->toDateString()), $tz)->endOfDay();
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }
        [$fromUtc, $toUtc] = [$from->copy()->utc(), $to->copy()->utc()];

        $payments = Payment::whereBetween('paid_at', [$fromUtc, $toUtc])->get(['amount_minor', 'method', 'paid_at']);
        $invoices = Invoice::whereBetween('created_at', [$fromUtc, $toUtc])
            ->whereIn('status', ['issued', 'partially_paid', 'paid'])
            ->get(['total_minor', 'status', 'issued_at', 'created_at']);
        $appointments = Appointment::whereBetween('starts_at', [$fromUtc, $toUtc])->get(['status', 'dentist_id', 'starts_at']);

        // Collected vs issued series across the range (chart), grouped in PHP (SQLite/MySQL safe).
        // Long ranges bucket by week (clinic week starts Saturday) so the chart stays readable.
        $spanDays = $from->diffInDays($to) + 1;
        $weekly = $spanDays > 40;
        $collectedSeries = [];
        $issuedSeries = [];
        for ($d = $from->copy(); $d->lte($to); $weekly ? $d->addWeek() : $d->addDay()) {
            $k = $weekly ? $d->copy()->startOfWeek(Carbon::SATURDAY)->toDateString() : $d->toDateString();
            $collectedSeries[$k] = 0;
            $issuedSeries[$k] = 0;
        }
        $bucketOf = fn (Carbon $c) => $weekly
            ? $c->copy()->timezone($tz)->startOfWeek(Carbon::SATURDAY)->toDateString()
            : $c->timezone($tz)->toDateString();
        foreach ($payments as $p) {
            $k = $bucketOf($p->paid_at);
            if (array_key_exists($k, $collectedSeries)) {
                $collectedSeries[$k] += $p->amount_minor;
            }
        }
        foreach ($invoices as $inv) {
            $k = $bucketOf($inv->issued_at ?? $inv->created_at);
            if (array_key_exists($k, $issuedSeries)) {
                $issuedSeries[$k] += $inv->total_minor;
            }
        }

        $byMethod = [];
        foreach (Payment::METHODS as $m) {
            $byMethod[$m] = 0;
        }
        foreach ($payments as $p) {
            $byMethod[$p->method] = ($byMethod[$p->method] ?? 0) + $p->amount_minor;
        }

        $byStatus = [];
        foreach (Appointment::STATUSES as $s) {
            $byStatus[$s] = 0;
        }
        foreach ($appointments as $a) {
            $byStatus[$a->status] = ($byStatus[$a->status] ?? 0) + 1;
        }

        // Per-dentist activity in range.
        $perDentist = $appointments->groupBy('dentist_id')->map(fn ($rows, $id) => [
            'dentist' => \App\Models\User::find($id)?->name ?? '—',
            'total' => $rows->count(),
            'completed' => $rows->where('status', 'completed')->count(),
            'no_show' => $rows->where('status', 'no_show')->count(),
            'cancelled' => $rows->where('status', 'cancelled')->count(),
        ])->sortByDesc('total')->values();

        // Doctor commissions: invoiced production (issued/paid invoices in range)
        // attributed to each dentist through plan items, times the dentist's rate.
        // Invoice items carry no clinic_id: scope through the parent invoice (tenant-safe).
        $baseByDentist = InvoiceItem::whereNotNull('treatment_plan_item_id')
            ->whereHas('invoice', fn ($q) => $q->where('clinic_id', $request->user()->clinic_id)
                ->whereBetween('created_at', [$fromUtc, $toUtc])
                ->whereIn('status', ['issued', 'partially_paid', 'paid']))
            ->with('treatmentPlanItem.plan:id,dentist_id')
            ->get(['treatment_plan_item_id', 'total_minor'])
            ->filter(fn ($i) => $i->treatmentPlanItem?->plan?->dentist_id)
            ->groupBy(fn ($i) => $i->treatmentPlanItem->plan->dentist_id)
            ->map(fn ($rows) => (int) $rows->sum('total_minor'));

        $commissions = User::where('role', User::ROLE_DENTIST)->where('is_active', true)
            ->get(['id', 'name', 'commission_bp'])
            ->map(fn ($d) => [
                'id' => $d->id,
                'dentist' => $d->name,
                'rate_bp' => (int) $d->commission_bp,
                'base_minor' => (int) ($baseByDentist[$d->id] ?? 0),
                'commission_minor' => (int) intdiv(($baseByDentist[$d->id] ?? 0) * $d->commission_bp, 10000),
            ])->values();

        // Top services by accepted/completed plan items created in range.
        // Items carry no clinic_id: scope through the parent plan (tenant-safe).
        $topServices = TreatmentPlanItem::whereIn('status', ['accepted', 'in_progress', 'completed'])
            ->whereBetween('created_at', [$fromUtc, $toUtc])
            ->whereNotNull('service_id')
            ->whereHas('plan', fn ($q) => $q->where('clinic_id', $request->user()->clinic_id))
            ->with('service:id,name_ar')
            ->get(['service_id', 'price_minor', 'discount_minor', 'qty'])
            ->groupBy('service_id')
            ->map(fn ($rows) => [
                'name' => $rows->first()->service?->name_ar ?? '—',
                'count' => $rows->sum('qty'),
                'total_minor' => $rows->sum(fn ($i) => ($i->price_minor - $i->discount_minor) * $i->qty),
            ])->sortByDesc('total_minor')->take(8)->values();

        $apptCount = $appointments->count();
        $completed = $byStatus['completed'] ?? 0;

        return [
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'currency' => $request->user()->clinic?->currency ?? 'EGP',
            'cards' => [
                'collected_minor' => (int) $payments->sum('amount_minor'),
                'invoiced_minor' => (int) $invoices->sum('total_minor'),
                'outstanding_minor' => (int) Invoice::whereIn('status', ['issued', 'partially_paid'])
                    ->selectRaw('COALESCE(SUM(total_minor - paid_minor),0) as b')->value('b'),
                'new_patients' => Patient::whereBetween('created_at', [$fromUtc, $toUtc])->count(),
                'appointments' => $apptCount,
                'completion_pct' => $apptCount ? round($completed / $apptCount * 100) : 0,
                'no_show_pct' => $apptCount ? round(($byStatus['no_show'] ?? 0) / $apptCount * 100) : 0,
            ],
            'byMethod' => $byMethod,
            'byStatus' => $byStatus,
            'commissions' => $commissions,
            'perDentist' => $perDentist,
            'topServices' => $topServices,
            'daily' => collect($collectedSeries)->map(fn ($v, $k) => [
                'label' => Carbon::parse($k)->format('j/n'),
                'a' => $v, 'b' => (int) ($issuedSeries[$k] ?? 0),
            ])->values(),
        ];
    }
}

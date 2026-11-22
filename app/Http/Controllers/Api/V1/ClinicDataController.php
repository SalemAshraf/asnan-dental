<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\ReminderMessage;
use Illuminate\Http\Request;

class ClinicDataController extends Controller
{
    public function appointments(Request $request)
    {
        $tz = $request->user()->clinic?->timezone ?? config('app.timezone');
        $date = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']])['date']
            ?? now($tz)->toDateString();

        $rows = Appointment::with(['patient:id,full_name,phone,file_no', 'dentist:id,name'])
            ->whereDate('starts_at', $date)
            ->orderBy('starts_at')
            ->limit(100)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'starts_at' => $a->starts_at?->toIso8601String(),
                'ends_at' => $a->ends_at?->toIso8601String(),
                'status' => $a->status,
                'chair' => $a->chair,
                'reason' => $a->reason,
                'patient' => $a->patient ? [
                    'id' => $a->patient->id,
                    'name' => $a->patient->full_name,
                    'file_no' => $a->patient->file_no,
                    'phone' => $a->patient->phone,
                ] : null,
                'dentist' => $a->dentist?->name,
            ]);

        return response()->json(['date' => $date, 'appointments' => $rows]);
    }

    public function patients(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        abort_if(mb_strlen($q) < 2, 422, 'q must be at least 2 characters');

        $rows = Patient::where(fn ($query) => $query
                ->where('full_name', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")
                ->orWhere('whatsapp', 'like', "%{$q}%"))
            ->orderBy('full_name')
            ->limit(10)
            ->get(['id', 'full_name', 'phone', 'whatsapp', 'file_no', 'gender', 'birth_date']);

        return response()->json(['patients' => $rows]);
    }

    public function reminders(Request $request)
    {
        $rows = ReminderMessage::with(['patient:id,full_name,phone,whatsapp,file_no', 'appointment:id,starts_at'])
            ->where('status', 'queued')
            ->orderBy('scheduled_for')
            ->limit(100)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'kind' => $m->kind,
                'scheduled_for' => $m->scheduled_for?->toDateString(),
                'starts_at' => $m->appointment?->starts_at?->toIso8601String(),
                'patient' => $m->patient ? [
                    'id' => $m->patient->id,
                    'name' => $m->patient->full_name,
                    'file_no' => $m->patient->file_no,
                    'phone' => $m->patient->phone,
                    'whatsapp' => $m->patient->whatsapp,
                ] : null,
            ]);

        return response()->json(['reminders' => $rows]);
    }

    public function markReminderSent(Request $request, ReminderMessage $reminder)
    {
        $reminder->update([
            'status' => 'sent',
            'sent_at' => now(),
            'sent_by' => $request->user()->id,
        ]);

        return response()->json(['ok' => true, 'id' => $reminder->id, 'status' => 'sent']);
    }
}

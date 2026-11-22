<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\BookingRequest;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingRequestController extends Controller
{
    /** Accept a request: find-or-create the patient by phone, book a confirmed appointment, link them. One transaction, one request. */
    public function accept(Request $request, BookingRequest $bookingRequest)
    {
        abort_if($bookingRequest->status !== BookingRequest::STATUS_NEW, 403);

        $d = $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'dentist_id' => 'required|integer|exists:users,id',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
        ]);
        $clinic = $request->user()->clinic;
        abort_unless($clinic->branches()->whereKey($d['branch_id'])->exists(), 422);
        abort_unless($clinic->users()->whereKey($d['dentist_id'])->exists(), 422);

        $tz = $clinic->timezone ?? config('app.timezone');
        $start = Carbon::parse($d['date'].' '.$d['time'], $tz);
        $duration = $bookingRequest->service?->duration_min ?? 30;
        $end = $start->copy()->addMinutes($duration);

        app(\App\Services\AppointmentService::class)->assertNoOverlap(
            (int) $d['branch_id'], (int) $d['dentist_id'], null, $start->copy()->utc(), $end->copy()->utc()
        );

        DB::transaction(function () use ($bookingRequest, $clinic, $d, $start, $end) {
            $patient = Patient::firstOrCreate(
                ['clinic_id' => $clinic->id, 'phone' => $bookingRequest->phone],
                ['full_name' => $bookingRequest->name, 'file_no' => $clinic->allocatePatientFileNo()]
            );
            $appointment = Appointment::create([
                'clinic_id' => $clinic->id,
                'branch_id' => $d['branch_id'],
                'patient_id' => $patient->id,
                'dentist_id' => $d['dentist_id'],
                'starts_at' => $start->copy()->utc(),
                'ends_at' => $end->copy()->utc(),
                'status' => 'confirmed',
                'reason' => $bookingRequest->service?->name_ar,
                'notes' => $bookingRequest->notes,
                'booked_via' => 'online',
            ]);
            $bookingRequest->forceFill(['status' => BookingRequest::STATUS_CONFIRMED, 'patient_id' => $patient->id, 'appointment_id' => $appointment->id])->save();
        });

        return back()->with('success', 'ok');
    }

    public function decline(BookingRequest $bookingRequest)
    {
        abort_if($bookingRequest->status !== BookingRequest::STATUS_NEW, 403);
        $bookingRequest->forceFill(['status' => BookingRequest::STATUS_DECLINED])->save();

        return back()->with('success', 'ok');
    }
}

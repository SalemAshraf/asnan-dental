<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\User;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    public function __construct(private AppointmentService $service) {}

    public function index(Request $request)
    {
        $tz = $request->user()->clinic?->timezone ?? config('app.timezone');
        $day = $request->date('day') ?? now($tz);
        $start = Carbon::instance($day)->setTimezone($tz)->startOfDay()->utc();
        $end = Carbon::instance($day)->setTimezone($tz)->endOfDay()->utc();

        $appointments = Appointment::with(['patient:id,full_name,phone', 'dentist:id,name', 'branch:id,name'])
            ->whereBetween('starts_at', [$start, $end])
            ->orderBy('starts_at')
            ->get();

        return Inertia::render('Appointments/Index', [
            'appointments' => $appointments,
            'day' => Carbon::instance($day)->setTimezone($tz)->toDateString(),
            'branches' => Branch::where('is_active', true)->get(['id', 'name']),
            'dentists' => User::where('is_active', true)->whereIn('role', ['owner', 'dentist'])->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $tz = $request->user()->clinic?->timezone ?? config('app.timezone');

        $this->service->assertNoOverlap(
            (int) $data['branch_id'], (int) $data['dentist_id'], $data['chair'] ?? null,
            Carbon::parse($data['starts_at'], $tz)->utc(), Carbon::parse($data['ends_at'], $tz)->utc()
        );

        $appointment = Appointment::create([
            ...$data,
            'starts_at' => Carbon::parse($data['starts_at'], $tz)->utc(),
            'ends_at' => Carbon::parse($data['ends_at'], $tz)->utc(),
            'status' => 'scheduled',
        ]);

        return redirect()->route('appointments.index', ['day' => Carbon::parse($data['starts_at'], $tz)->toDateString()]);
    }

    public function update(Request $request, Appointment $appointment)
    {
        $data = $this->validateData($request);
        $tz = $request->user()->clinic?->timezone ?? config('app.timezone');

        $this->service->assertNoOverlap(
            (int) $data['branch_id'], (int) $data['dentist_id'], $data['chair'] ?? null,
            Carbon::parse($data['starts_at'], $tz)->utc(), Carbon::parse($data['ends_at'], $tz)->utc(),
            $appointment->id
        );

        $data['starts_at'] = Carbon::parse($data['starts_at'], $tz)->utc();
        $data['ends_at'] = Carbon::parse($data['ends_at'], $tz)->utc();
        $appointment->update($data);

        return back();
    }

    public function transition(Request $request, Appointment $appointment)
    {
        $data = $request->validate(['status' => ['required', Rule::in(Appointment::STATUSES)]]);
        $appointment->update(['status' => $data['status']]);

        return back();
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'dentist_id' => ['required', 'integer', 'exists:users,id'],
            'chair' => ['nullable', 'string', 'max:32'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}

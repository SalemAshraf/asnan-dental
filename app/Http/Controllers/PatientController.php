<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $query = Patient::query()->orderByDesc('id');

        if ($search = trim((string) $request->query('q'))) {
            $query->where(fn ($q) => $q->where('full_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('file_no', (int) $search ?: 0));
        }

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'patients' => $query->limit(10)->get(['id', 'full_name', 'phone', 'file_no']),
            ]);
        }

        return Inertia::render('Patients/Index', [
            'patients' => $query->paginate(15)->withQueryString(),
            'filters' => ['q' => $request->query('q', '')],
        ]);
    }

    public function create()
    {
        return Inertia::render('Patients/Form', ['patient' => null]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $clinic = Clinic::findOrFail($request->user()->clinic_id);
        $data['file_no'] = $clinic->allocatePatientFileNo();
        $data['branch_id'] = $data['branch_id'] ?? $request->user()->branch_id;

        $patient = Patient::create($data);

        return redirect()->route('patients.show', $patient);
    }

    public function show(Patient $patient)
    {
        $user = request()->user();

        $load = [
            'consents' => fn ($q) => $q->orderByDesc('given_at'),
            'treatmentPlans' => fn ($q) => $q->with('items')->orderByDesc('id'),
            'invoices' => fn ($q) => $q->orderByDesc('id')->limit(20),
        ];
        if ($user->isClinical()) {
            $load['images'] = fn ($q) => $q->with('uploader:id,name');
            $load['prescriptions'] = fn ($q) => $q->with(['items', 'prescriber:id,name']);
        }
        $patient->load($load);

        $appointments = $patient->appointments()
            ->with(['dentist:id,name', 'branch:id,name'])
            ->orderByDesc('starts_at')->limit(20)->get();

        // Sensitive medical alerts are clinical/owner-only.
        $attrs = $patient->toArray();
        if (! $user->isClinical()) {
            $attrs['medical_alerts'] = null;
            $attrs['national_id'] = null;
        }

        return Inertia::render('Patients/Show', [
            'patient' => $attrs,
            'appointments' => $appointments,
            'chart' => \App\Models\OdontogramEntry::chartFor($patient),
            'can' => [
                'clinical' => $user->isClinical(),
                'billing' => in_array($user->role, ['owner', 'receptionist'], true),
            ],
        ]);
    }

    public function edit(Patient $patient)
    {
        $user = request()->user();
        $attrs = $patient->toArray();
        if (! $user->isClinical()) {
            $attrs['medical_alerts'] = null;
            $attrs['national_id'] = null;
        }

        return Inertia::render('Patients/Form', ['patient' => $attrs]);
    }

    public function update(Request $request, Patient $patient)
    {
        $data = $this->validateData($request, $patient->id);

        if (! $request->user()->isClinical()) {
            unset($data['medical_alerts'], $data['national_id']);
        }

        $patient->update($data);

        return redirect()->route('patients.show', $patient);
    }

    public function destroy(Patient $patient)
    {
        abort_unless(request()->user()->isOwner(), 403);
        $patient->delete();

        return redirect()->route('patients.index');
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
            'whatsapp' => ['nullable', 'string', 'max:32'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'national_id' => ['nullable', 'string', 'max:64'],
            'medical_alerts' => ['nullable', 'array'],
            'medical_alerts.*' => ['string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);
    }
}

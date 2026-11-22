<?php

namespace App\Http\Controllers;

use App\Models\OdontogramEntry;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OdontogramController extends Controller
{
    public function upsert(Request $request, Patient $patient)
    {
        abort_unless($request->user()->isClinical(), 403);

        $data = $request->validate([
            'tooth_number' => ['required', 'integer', Rule::in(OdontogramEntry::validTeeth())],
            'surface' => ['nullable', Rule::in(OdontogramEntry::SURFACES)],
            'condition' => ['required', Rule::in(OdontogramEntry::CONDITIONS)],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        OdontogramEntry::updateOrCreate(
            [
                'patient_id' => $patient->id,
                'tooth_number' => $data['tooth_number'],
                'surface' => $data['surface'] ?? null,
            ],
            [
                'condition' => $data['condition'],
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $request->user()->id,
            ]
        );

        return back();
    }
}

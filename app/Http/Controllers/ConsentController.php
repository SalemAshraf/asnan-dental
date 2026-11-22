<?php

namespace App\Http\Controllers;

use App\Models\ConsentRecord;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConsentController extends Controller
{
    public function store(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(ConsentRecord::TYPES)],
            'text_snapshot' => ['required', 'string'],
            'method' => ['required', Rule::in(ConsentRecord::METHODS)],
            'given_at' => ['required', 'date'],
        ]);

        ConsentRecord::create([
            ...$data,
            'patient_id' => $patient->id,
            'recorded_by' => $request->user()->id,
        ]);

        return back();
    }
}

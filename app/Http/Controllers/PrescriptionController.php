<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    private function validateData(Request $request): array
    {
        return $request->validate([
            'prescribed_on' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.drug_name' => ['required', 'string', 'max:190'],
            'items.*.dose' => ['nullable', 'string', 'max:120'],
            'items.*.frequency' => ['nullable', 'string', 'max:120'],
            'items.*.duration' => ['nullable', 'string', 'max:120'],
            'items.*.instructions' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function syncItems(Prescription $prescription, array $items): void
    {
        $prescription->items()->delete();
        $prescription->items()->createMany(collect($items)->map(fn ($item, $i) => [
            'drug_name' => $item['drug_name'],
            'dose' => $item['dose'] ?? null,
            'frequency' => $item['frequency'] ?? null,
            'duration' => $item['duration'] ?? null,
            'instructions' => $item['instructions'] ?? null,
            'sort' => $i,
        ])->all());
    }

    public function store(Request $request, Patient $patient)
    {
        abort_unless($request->user()->isClinical(), 403);
        $data = $this->validateData($request);

        $prescription = $patient->prescriptions()->create([
            'user_id' => $request->user()->id,
            'prescribed_on' => $data['prescribed_on'],
            'notes' => $data['notes'] ?? null,
        ]);
        $this->syncItems($prescription, $data['items']);

        return back();
    }

    public function update(Request $request, Prescription $prescription)
    {
        abort_unless($request->user()->isClinical(), 403);
        $data = $this->validateData($request);

        $prescription->update([
            'prescribed_on' => $data['prescribed_on'],
            'notes' => $data['notes'] ?? null,
        ]);
        $this->syncItems($prescription, $data['items']);

        return back();
    }

    public function destroy(Request $request, Prescription $prescription)
    {
        abort_unless($request->user()->isClinical(), 403);

        $prescription->delete();

        return back();
    }
}

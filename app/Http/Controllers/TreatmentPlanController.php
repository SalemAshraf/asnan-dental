<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class TreatmentPlanController extends Controller
{
    public function store(Request $request, Patient $patient)
    {
        abort_unless($request->user()->isClinical(), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $plan = TreatmentPlan::create([
            ...$data,
            'patient_id' => $patient->id,
            'dentist_id' => $request->user()->id,
        ]);

        return back();
    }

    public function transition(Request $request, TreatmentPlan $plan)
    {
        abort_unless($request->user()->isClinical(), 403);

        $data = $request->validate(['status' => ['required', Rule::in(TreatmentPlan::STATUSES)]]);

        try {
            $plan->transitionTo($data['status']);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back();
    }

    public function storeItem(Request $request, TreatmentPlan $plan)
    {
        abort_unless($request->user()->isClinical(), 403);
        abort_unless(in_array($plan->status, ['draft', 'proposed'], true), 422);

        $data = $request->validate([
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'tooth_number' => ['nullable', 'integer', Rule::in(\App\Models\OdontogramEntry::validTeeth())],
            'surfaces' => ['nullable', 'string', 'max:8'],
            'description' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        TreatmentPlanItem::create([
            'plan_id' => $plan->id,
            'service_id' => $data['service_id'] ?? null,
            'tooth_number' => $data['tooth_number'] ?? null,
            'surfaces' => $data['surfaces'] ?? null,
            'description' => $data['description'],
            'price_minor' => (int) round(((float) $data['price']) * 100),
            'discount_minor' => (int) round(((float) ($data['discount'] ?? 0)) * 100),
            'qty' => $data['qty'] ?? 1,
            'sort' => ($plan->items()->max('sort') ?? 0) + 1,
        ]);

        $plan->recalcTotal();

        return back();
    }

    public function updateItem(Request $request, TreatmentPlanItem $item)
    {
        abort_unless($request->user()->isClinical(), 403);

        $data = $request->validate([
            'status' => ['sometimes', Rule::in(TreatmentPlanItem::STATUSES)],
        ]);

        $wasCompleted = $item->status === 'completed';
        $item->update($data);
        if (! $wasCompleted && $item->status === 'completed') {
            \App\Support\InventoryDeduction::deductForPlanItem($item, $request->user()->id);
        }
        $item->plan->recalcTotal();

        return back();
    }
}

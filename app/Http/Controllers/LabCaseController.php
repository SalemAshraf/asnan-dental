<?php

namespace App\Http\Controllers;

use App\Models\LabCase;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class LabCaseController extends Controller
{
    public function index(Request $request)
    {
        $open = LabCase::with(['patient:id,full_name,file_no', 'dentist:id,name'])
            ->whereIn('status', LabCase::OPEN_STATUSES)
            ->orderByRaw('CASE WHEN due_on IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_on')
            ->latest('id')
            ->limit(200)
            ->get();

        $closed = LabCase::with(['patient:id,full_name,file_no', 'dentist:id,name'])
            ->whereIn('status', ['fitted', 'cancelled'])
            ->latest('updated_at')
            ->limit(15)
            ->get();

        return Inertia::render('Lab/Index', [
            'open' => $open,
            'closed' => $closed,
            'dentists' => User::where('role', User::ROLE_DENTIST)->where('is_active', true)->get(['id', 'name']),
        ]);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'patient_id' => ['nullable', 'integer', 'exists:patients,id'],
            'dentist_id' => ['nullable', 'integer', 'exists:users,id'],
            'lab_name' => ['required', 'string', 'max:190'],
            'work_type' => ['required', Rule::in(LabCase::WORK_TYPES)],
            'shade' => ['nullable', 'string', 'max:60'],
            'sent_on' => ['required', 'date'],
            'due_on' => ['nullable', 'date', 'after_or_equal:sent_on'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        LabCase::create([
            ...$data,
            'cost_minor' => isset($data['cost']) ? (int) round($data['cost'] * 100) : null,
            'status' => 'sent',
        ]);

        return back();
    }

    public function update(Request $request, LabCase $labCase)
    {
        $data = $this->validateData($request);

        $labCase->update([
            ...$data,
            'cost_minor' => isset($data['cost']) ? (int) round($data['cost'] * 100) : null,
        ]);

        return back();
    }

    public function transition(Request $request, LabCase $labCase)
    {
        $data = $request->validate(['status' => ['required', Rule::in(LabCase::STATUSES)]]);

        $labCase->update(['status' => $data['status']]);

        return back();
    }

    public function destroy(Request $request, LabCase $labCase)
    {
        abort_unless($request->user()->isOwner(), 403);

        $labCase->delete();

        return back();
    }
}

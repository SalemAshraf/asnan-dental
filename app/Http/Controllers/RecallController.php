<?php

namespace App\Http\Controllers;

use App\Models\Recall;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class RecallController extends Controller
{
    public function index(Request $request)
    {
        $tz = $request->user()->clinic?->timezone ?? config('app.timezone');
        $today = now($tz)->toDateString();

        $pending = Recall::with(['patient:id,full_name,phone,whatsapp,file_no', 'service:id,name_ar'])
            ->where('status', 'pending')
            ->orderBy('due_date')
            ->limit(100)
            ->get()
            ->map(fn ($r) => [
                ...$r->toArray(),
                'overdue' => $r->due_date->toDateString() < $today,
            ]);

        return Inertia::render('Recalls/Index', [
            'pending' => $pending,
            'recentDone' => Recall::with(['patient:id,full_name,file_no', 'service:id,name_ar'])
                ->whereIn('status', ['done', 'cancelled'])
                ->latest('updated_at')
                ->limit(15)
                ->get(),
            'today' => $today,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'due_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        Recall::create([
            ...$data,
            'status' => 'pending',
            'created_by' => $request->user()->id,
        ]);

        return back();
    }

    public function transition(Request $request, Recall $recall)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['done', 'cancelled', 'pending'])]]);
        $recall->update([
            'status' => $data['status'],
            'done_at' => $data['status'] === 'done' ? now() : null,
        ]);

        return back();
    }
}

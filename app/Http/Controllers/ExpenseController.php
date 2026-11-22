<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isOwner(), 403);

        $tz = $request->user()->clinic?->timezone ?? config('app.timezone');
        $month = $request->input('month', now($tz)->format('Y-m'));
        $start = \Carbon\Carbon::createFromFormat('Y-m', $month, $tz)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $expenses = Expense::with('creator:id,name')
            ->whereBetween('spent_on', [$start->toDateString(), $end->toDateString()])
            ->latest('spent_on')
            ->latest('id')
            ->limit(300)
            ->get();

        $byCategory = $expenses->groupBy('category')
            ->map(fn ($rows) => $rows->sum('amount_minor'))
            ->sortDesc();

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'byCategory' => $byCategory,
            'month' => $month,
            'monthTotal' => $expenses->sum('amount_minor'),
            'currency' => $request->user()->clinic?->currency ?? 'EGP',
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isOwner(), 403);

        $data = $request->validate([
            'category' => ['required', Rule::in(Expense::CATEGORIES)],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'spent_on' => ['required', 'date'],
            'vendor' => ['nullable', 'string', 'max:190'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        Expense::create([
            'category' => $data['category'],
            'amount_minor' => (int) round($data['amount'] * 100),
            'spent_on' => $data['spent_on'],
            'vendor' => $data['vendor'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return back();
    }

    public function destroy(Request $request, Expense $expense)
    {
        abort_unless($request->user()->isOwner(), 403);

        $expense->delete();

        return back();
    }
}

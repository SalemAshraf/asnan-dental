<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\TreatmentPlan;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $service) {}

    public function index()
    {
        return Inertia::render('Invoices/Index', [
            'invoices' => Invoice::with('patient:id,full_name,file_no')
                ->orderByDesc('id')->paginate(15),
        ]);
    }

    /** Create a draft invoice from accepted treatment-plan items. */
    public function storeFromPlan(Request $request, TreatmentPlan $plan)
    {
        abort_unless(in_array($request->user()->role, ['owner', 'dentist', 'receptionist'], true), 403);

        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['integer'],
        ]);

        $items = $plan->items()->whereIn('id', $data['item_ids'])->where('status', 'accepted')->get();
        if ($items->isEmpty()) {
            return back()->withErrors(['item_ids' => ['لا توجد بنود مقبولة صالحة للفوترة.']]);
        }

        $clinic = $request->user()->clinic;
        $invoice = Invoice::create([
            'branch_id' => $data['branch_id'],
            'patient_id' => $plan->patient_id,
            'status' => 'draft',
            'currency' => $clinic->currency,
            'tax_rate_bp' => $clinic->tax_rate_bp,
        ]);

        foreach ($items as $item) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'treatment_plan_item_id' => $item->id,
                'description' => $item->description,
                'tooth_number' => $item->tooth_number,
                'qty' => $item->qty,
                'unit_price_minor' => $item->price_minor,
                'discount_minor' => $item->discount_minor,
                'total_minor' => $item->lineTotal(),
            ]);
        }

        $this->service->recalcTotals($invoice);

        return redirect()->route('invoices.show', $invoice);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['items', 'payments' => fn ($q) => $q->orderByDesc('paid_at'), 'patient:id,full_name,file_no,phone']);

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice,
            'paymentMethods' => Payment::METHODS,
        ]);
    }

    public function issue(Invoice $invoice)
    {
        $this->service->issue($invoice);

        return back();
    }

    public function void(Invoice $invoice)
    {
        abort_unless(request()->user()->isOwner(), 403);
        abort_unless(in_array($invoice->status, ['draft', 'issued'], true), 422);
        abort_if($invoice->payments()->exists(), 422);

        $invoice->update(['status' => 'void']);

        return back();
    }

    public function addPayment(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', Rule::in(Payment::METHODS)],
            'reference' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->service->recordPayment($invoice, [
            'amount_minor' => (int) round(((float) $data['amount']) * 100),
            'method' => $data['method'],
            'reference' => $data['reference'] ?? null,
            'paid_at' => $data['paid_at'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return back();
    }
}

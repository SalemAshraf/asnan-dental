<?php

namespace App\Http\Controllers;

use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class InsuranceController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Insurance/Index', [
            'companies' => InsuranceCompany::withCount('claims')->orderBy('name')->get(),
            'openClaims' => InsuranceClaim::with(['patient:id,full_name,file_no', 'company:id,name'])
                ->whereIn('status', InsuranceClaim::OPEN_STATUSES)
                ->latest('id')->limit(200)->get(),
            'closedClaims' => InsuranceClaim::with(['patient:id,full_name,file_no', 'company:id,name'])
                ->whereIn('status', ['rejected', 'paid'])
                ->latest('updated_at')->limit(15)->get(),
        ]);
    }

    public function storeCompany(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:190'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        InsuranceCompany::create($data);

        return back();
    }

    public function storeClaim(Request $request)
    {
        $data = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'insurance_company_id' => ['required', 'integer', 'exists:insurance_companies,id'],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'claim_no' => ['nullable', 'string', 'max:60'],
            'amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'submitted_on' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        // Tenant-safe lookups: the BelongsToClinic global scope makes these 404 cross-tenant.
        InsuranceCompany::findOrFail($data['insurance_company_id']);
        \App\Models\Patient::findOrFail($data['patient_id']);
        if (! empty($data['invoice_id'])) {
            \App\Models\Invoice::findOrFail($data['invoice_id']);
        }

        InsuranceClaim::create([
            ...$data,
            'amount' => (int) round($data['amount'] * 100),
            'status' => 'submitted',
        ]);

        return back();
    }

    public function updateClaim(Request $request, InsuranceClaim $insuranceClaim)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(InsuranceClaim::STATUSES)],
            'decided_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['status'] !== 'submitted' && empty($data['decided_on'])) {
            $data['decided_on'] = now()->toDateString();
        }

        $insuranceClaim->update($data);

        return back();
    }
}

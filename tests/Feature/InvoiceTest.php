<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Clinic;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function planWithItems(User $user, array $itemStatuses): TreatmentPlan
    {
        $branch = Branch::factory()->create(['clinic_id' => $user->clinic_id]);
        $patient = Patient::factory()->create(['clinic_id' => $user->clinic_id, 'branch_id' => $branch->id]);
        $plan = TreatmentPlan::factory()->create([
            'clinic_id' => $user->clinic_id, 'patient_id' => $patient->id, 'dentist_id' => $user->id,
        ]);
        foreach ($itemStatuses as $i => $status) {
            TreatmentPlanItem::create([
                'plan_id' => $plan->id, 'description' => 'بند '.$i, 'price_minor' => 100000,
                'discount_minor' => 0, 'qty' => 1, 'status' => $status,
            ]);
        }

        return $plan;
    }

    public function test_invoice_from_plan_uses_only_accepted_items(): void
    {
        $user = User::factory()->create();
        $plan = $this->planWithItems($user, ['accepted', 'proposed', 'accepted']);
        $acceptedIds = $plan->items()->where('status', 'accepted')->pluck('id');

        $this->actingAs($user)->post("/plans/{$plan->id}/invoice", [
            'branch_id' => $plan->patient->branch_id ?? Branch::factory()->create(['clinic_id' => $user->clinic_id])->id,
            'item_ids' => $plan->items->pluck('id')->all(),
        ])->assertRedirect();

        $invoice = Invoice::first();
        $this->assertCount(2, $invoice->items);
        $this->assertSame(200000, $invoice->subtotal_minor);
    }

    public function test_tax_and_totals_computed_server_side(): void
    {
        $user = User::factory()->create();
        $user->clinic->update(['tax_rate_bp' => 1400]); // 14%
        $plan = $this->planWithItems($user, ['accepted']);
        $branch = Branch::where('clinic_id', $user->clinic_id)->first();

        $this->actingAs($user)->post("/plans/{$plan->id}/invoice", [
            'branch_id' => $branch->id, 'item_ids' => $plan->items->pluck('id')->all(),
        ]);

        $invoice = Invoice::first();
        $this->assertSame(100000, $invoice->subtotal_minor);
        $this->assertSame(14000, $invoice->tax_minor);
        $this->assertSame(114000, $invoice->total_minor);
    }

    public function test_issue_allocates_sequential_numbers_per_clinic(): void
    {
        $user = User::factory()->create();
        $branch = Branch::factory()->create(['clinic_id' => $user->clinic_id]);
        $patient = Patient::factory()->create(['clinic_id' => $user->clinic_id]);

        $n = [];
        foreach ([1, 2] as $_) {
            $invoice = Invoice::factory()->create([
                'clinic_id' => $user->clinic_id, 'branch_id' => $branch->id, 'patient_id' => $patient->id,
            ]);
            InvoiceItem::create([
                'invoice_id' => $invoice->id, 'description' => 'كشف', 'qty' => 1,
                'unit_price_minor' => 30000, 'discount_minor' => 0, 'total_minor' => 30000,
            ]);
            app(\App\Services\InvoiceService::class)->recalcTotals($invoice);
            $this->actingAs($user)->post("/invoices/{$invoice->id}/issue");
            $n[] = $invoice->fresh()->number;
        }

        $this->assertSame(['INV-000001', 'INV-000002'], $n);
    }

    public function test_installment_payments_move_status_to_paid(): void
    {
        $user = User::factory()->receptionist()->create();
        $branch = Branch::factory()->create(['clinic_id' => $user->clinic_id]);
        $patient = Patient::factory()->create(['clinic_id' => $user->clinic_id]);
        $invoice = Invoice::factory()->create([
            'clinic_id' => $user->clinic_id, 'branch_id' => $branch->id,
            'patient_id' => $patient->id, 'status' => 'issued',
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id, 'description' => 'علاج عصب', 'qty' => 1,
            'unit_price_minor' => 250000, 'discount_minor' => 0, 'total_minor' => 250000,
        ]);
        app(\App\Services\InvoiceService::class)->recalcTotals($invoice);

        $this->actingAs($user)->post("/invoices/{$invoice->id}/payments", [
            'amount' => '1000', 'method' => 'instapay',
        ])->assertSessionDoesntHaveErrors();
        $this->assertSame('partially_paid', $invoice->fresh()->status);
        $this->assertSame(100000, $invoice->fresh()->paid_minor);

        $this->actingAs($user)->post("/invoices/{$invoice->id}/payments", [
            'amount' => '1500', 'method' => 'cash',
        ])->assertSessionDoesntHaveErrors();
        $this->assertSame('paid', $invoice->fresh()->status);
    }

    public function test_overpayment_rejected(): void
    {
        $user = User::factory()->create();
        $branch = Branch::factory()->create(['clinic_id' => $user->clinic_id]);
        $patient = Patient::factory()->create(['clinic_id' => $user->clinic_id]);
        $invoice = Invoice::factory()->create([
            'clinic_id' => $user->clinic_id, 'branch_id' => $branch->id,
            'patient_id' => $patient->id, 'status' => 'issued', 'total_minor' => 50000,
        ]);
        $invoice->update(['total_minor' => 50000]);

        $this->actingAs($user)->post("/invoices/{$invoice->id}/payments", [
            'amount' => '600', 'method' => 'cash',
        ])->assertSessionHasErrors('amount_minor');
    }

    public function test_draft_invoice_cannot_take_payment_and_issued_cannot_be_reissued(): void
    {
        $user = User::factory()->create();
        $branch = Branch::factory()->create(['clinic_id' => $user->clinic_id]);
        $patient = Patient::factory()->create(['clinic_id' => $user->clinic_id]);
        $invoice = Invoice::factory()->create([
            'clinic_id' => $user->clinic_id, 'branch_id' => $branch->id, 'patient_id' => $patient->id,
        ]);

        $this->actingAs($user)->post("/invoices/{$invoice->id}/payments", [
            'amount' => '100', 'method' => 'cash',
        ])->assertSessionHasErrors('invoice');

        InvoiceItem::create([
            'invoice_id' => $invoice->id, 'description' => 'كشف', 'qty' => 1,
            'unit_price_minor' => 10000, 'discount_minor' => 0, 'total_minor' => 10000,
        ]);
        app(\App\Services\InvoiceService::class)->recalcTotals($invoice);
        $this->actingAs($user)->post("/invoices/{$invoice->id}/issue");
        $this->actingAs($user)->post("/invoices/{$invoice->id}/issue")
            ->assertSessionHasErrors('status');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseCommissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_record_list_and_delete_expenses(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->post('/expenses', [
            'category' => 'supplies', 'amount' => '250.50', 'spent_on' => '2026-11-10', 'vendor' => 'مورد الأسنان',
        ])->assertRedirect();

        $expense = Expense::first();
        $this->assertNotNull($expense);
        $this->assertSame(25050, $expense->amount_minor);
        $this->assertSame($owner->clinic_id, $expense->clinic_id);
        $this->assertSame($owner->id, $expense->created_by);

        $this->actingAs($owner)->get('/expenses?month=2026-11')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('monthTotal', 25050)->has('expenses', 1));

        $this->actingAs($owner)->delete("/expenses/{$expense->id}")->assertRedirect();
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_non_owner_cannot_access_expenses(): void
    {
        $owner = User::factory()->create();
        $receptionist = User::factory()->receptionist()->create(['clinic_id' => $owner->clinic_id]);
        $expense = Expense::create([
            'clinic_id' => $owner->clinic_id, 'category' => 'rent', 'amount_minor' => 100000,
            'spent_on' => '2026-11-01', 'created_by' => $owner->id,
        ]);

        $this->actingAs($receptionist)->get('/expenses')->assertForbidden();
        $this->actingAs($receptionist)->post('/expenses', [
            'category' => 'rent', 'amount' => '10', 'spent_on' => '2026-11-01',
        ])->assertForbidden();
        $this->actingAs($receptionist)->delete("/expenses/{$expense->id}")->assertForbidden();
        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }

    public function test_expenses_are_tenant_scoped(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $foreign = Expense::create([
            'clinic_id' => $other->clinic_id, 'category' => 'rent', 'amount_minor' => 50000,
            'spent_on' => '2026-11-01', 'created_by' => $other->id,
        ]);

        $this->actingAs($owner)->delete("/expenses/{$foreign->id}")->assertNotFound();
        $this->assertDatabaseHas('expenses', ['id' => $foreign->id]);
    }

    public function test_owner_sets_dentist_commission_rate_and_report_computes_it(): void
    {
        $owner = User::factory()->create();
        $clinicId = $owner->clinic_id;
        $branch = Branch::factory()->create(['clinic_id' => $clinicId]);
        $dentist = User::factory()->dentist()->create(['clinic_id' => $clinicId]);
        $patient = Patient::factory()->create(['clinic_id' => $clinicId]);

        $this->actingAs($owner)->patch("/team/{$dentist->id}/commission", ['commission_bp' => 2000])->assertRedirect();
        $this->assertSame(2000, $dentist->fresh()->commission_bp);

        // Invoiced production: 1000.00 on a plan item owned by this dentist.
        $plan = TreatmentPlan::factory()->create(['clinic_id' => $clinicId, 'patient_id' => $patient->id, 'dentist_id' => $dentist->id]);
        $planItem = TreatmentPlanItem::create([
            'plan_id' => $plan->id, 'description' => 'حشو', 'price_minor' => 100000,
            'discount_minor' => 0, 'qty' => 1, 'status' => 'completed',
        ]);
        $invoice = Invoice::create([
            'clinic_id' => $clinicId, 'branch_id' => $branch->id, 'patient_id' => $patient->id,
            'number' => 'INV-1', 'status' => 'issued', 'currency' => 'EGP',
            'subtotal_minor' => 100000, 'discount_minor' => 0, 'tax_rate_bp' => 0,
            'tax_minor' => 0, 'total_minor' => 100000, 'paid_minor' => 0, 'issued_at' => now(),
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id, 'treatment_plan_item_id' => $planItem->id,
            'description' => 'حشو', 'qty' => 1, 'unit_price_minor' => 100000,
            'discount_minor' => 0, 'total_minor' => 100000,
        ]);

        $this->actingAs($owner)->get('/reports')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('commissions.0.id', $dentist->id)
                ->where('commissions.0.rate_bp', 2000)
                ->where('commissions.0.base_minor', 100000)
                ->where('commissions.0.commission_minor', 20000));
    }

    public function test_non_owner_cannot_change_commission_rate(): void
    {
        $owner = User::factory()->create();
        $dentist = User::factory()->dentist()->create(['clinic_id' => $owner->clinic_id]);

        $this->actingAs($dentist)->patch("/team/{$dentist->id}/commission", ['commission_bp' => 5000])->assertForbidden();
        $this->assertSame(0, $dentist->fresh()->commission_bp);
    }
}

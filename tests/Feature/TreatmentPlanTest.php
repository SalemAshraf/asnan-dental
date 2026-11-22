<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\TreatmentPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TreatmentPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_lifecycle_enforced(): void
    {
        $dentist = User::factory()->dentist()->create();
        $patient = Patient::factory()->create(['clinic_id' => $dentist->clinic_id]);

        $this->actingAs($dentist)->post("/patients/{$patient->id}/plans", ['title' => 'خطة تقويم']);
        $plan = TreatmentPlan::first();
        $this->assertSame('draft', $plan->status);

        // invalid jump
        $this->actingAs($dentist)->post("/plans/{$plan->id}/transition", ['status' => 'completed'])
            ->assertSessionHasErrors('status');

        foreach (['proposed', 'accepted', 'in_progress', 'completed'] as $s) {
            $this->actingAs($dentist)->post("/plans/{$plan->id}/transition", ['status' => $s])
                ->assertSessionDoesntHaveErrors();
        }
        $this->assertSame('completed', $plan->fresh()->status);
    }

    public function test_items_recalc_plan_total(): void
    {
        $dentist = User::factory()->dentist()->create();
        $patient = Patient::factory()->create(['clinic_id' => $dentist->clinic_id]);
        $plan = TreatmentPlan::factory()->create([
            'clinic_id' => $dentist->clinic_id, 'patient_id' => $patient->id, 'dentist_id' => $dentist->id,
        ]);

        $this->actingAs($dentist)->post("/plans/{$plan->id}/items", [
            'description' => 'حشو ضوئي', 'price' => '900.00', 'discount' => '100', 'qty' => 2, 'tooth_number' => 16,
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame((90000 - 10000) * 2, $plan->fresh()->total_minor);
    }

    public function test_receptionist_cannot_write_clinical(): void
    {
        $receptionist = User::factory()->receptionist()->create();
        $patient = Patient::factory()->create(['clinic_id' => $receptionist->clinic_id]);

        $this->actingAs($receptionist)->post("/patients/{$patient->id}/plans", ['title' => 'خطة'])
            ->assertForbidden();
    }
}

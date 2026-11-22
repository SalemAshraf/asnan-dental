<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrescriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_clinical_user_can_create_edit_and_delete_prescription(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['clinic_id' => $user->clinic_id]);

        $this->actingAs($user)->post("/patients/{$patient->id}/prescriptions", [
            'prescribed_on' => '2026-11-20',
            'notes' => 'بعد الخلع',
            'items' => [
                ['drug_name' => 'دواء تجريبي أ', 'dose' => '500 مجم', 'frequency' => 'مرتين يوميًا', 'duration' => '3 أيام', 'instructions' => 'بعد الأكل'],
                ['drug_name' => 'دواء تجريبي ب'],
            ],
        ])->assertRedirect();

        $rx = Prescription::with('items')->first();
        $this->assertNotNull($rx);
        $this->assertSame($user->clinic_id, $rx->clinic_id);
        $this->assertSame($user->id, $rx->user_id);
        $this->assertCount(2, $rx->items);
        $this->assertSame('دواء تجريبي أ', $rx->items[0]->drug_name);

        $this->actingAs($user)->put("/prescriptions/{$rx->id}", [
            'prescribed_on' => '2026-11-21',
            'items' => [
                ['drug_name' => 'دواء تجريبي ج', 'dose' => '1 جم', 'frequency' => '3 مرات', 'duration' => 'أسبوع'],
            ],
        ])->assertRedirect();

        $rx->refresh();
        $this->assertSame('2026-11-21', $rx->prescribed_on->toDateString());
        $this->assertCount(1, $rx->items()->get());
        $this->assertSame('دواء تجريبي ج', $rx->items()->first()->drug_name);

        $this->actingAs($user)->delete("/prescriptions/{$rx->id}")->assertRedirect();
        $this->assertDatabaseMissing('prescriptions', ['id' => $rx->id]);
        $this->assertDatabaseMissing('prescription_items', ['prescription_id' => $rx->id]);
    }

    public function test_prescription_requires_at_least_one_drug(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['clinic_id' => $user->clinic_id]);

        $this->actingAs($user)->post("/patients/{$patient->id}/prescriptions", [
            'prescribed_on' => '2026-11-20',
            'items' => [['drug_name' => '']],
        ])->assertSessionHasErrors('items.0.drug_name');
        $this->assertSame(0, Prescription::count());
    }

    public function test_non_clinical_user_cannot_manage_prescriptions(): void
    {
        $owner = User::factory()->create();
        $receptionist = User::factory()->receptionist()->create(['clinic_id' => $owner->clinic_id]);
        $patient = Patient::factory()->create(['clinic_id' => $owner->clinic_id]);
        $rx = Prescription::create([
            'clinic_id' => $owner->clinic_id, 'patient_id' => $patient->id,
            'user_id' => $owner->id, 'prescribed_on' => '2026-11-20',
        ]);

        $this->actingAs($receptionist)->post("/patients/{$patient->id}/prescriptions", [
            'prescribed_on' => '2026-11-20', 'items' => [['drug_name' => 'x']],
        ])->assertForbidden();
        $this->actingAs($receptionist)->put("/prescriptions/{$rx->id}", [
            'prescribed_on' => '2026-11-20', 'items' => [['drug_name' => 'x']],
        ])->assertForbidden();
        $this->actingAs($receptionist)->delete("/prescriptions/{$rx->id}")->assertForbidden();
    }

    public function test_prescriptions_are_tenant_scoped(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreignPatient = Patient::factory()->create(['clinic_id' => $other->clinic_id]);
        $foreign = Prescription::create([
            'clinic_id' => $other->clinic_id, 'patient_id' => $foreignPatient->id,
            'user_id' => $other->id, 'prescribed_on' => '2026-11-20',
        ]);

        $this->actingAs($user)->put("/prescriptions/{$foreign->id}", [
            'prescribed_on' => '2026-11-21', 'items' => [['drug_name' => 'x']],
        ])->assertNotFound();
        $this->actingAs($user)->delete("/prescriptions/{$foreign->id}")->assertNotFound();
    }
}

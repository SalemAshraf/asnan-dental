<?php

namespace Tests\Feature;

use App\Models\ConsentRecord;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_consent_is_recorded_with_snapshot_and_recorder(): void
    {
        $user = User::factory()->receptionist()->create();
        $patient = Patient::factory()->create(['clinic_id' => $user->clinic_id]);

        $this->actingAs($user)->post("/patients/{$patient->id}/consents", [
            'type' => 'data_processing',
            'text_snapshot' => 'أوافق على معالجة بياناتي الصحية لغرض العلاج وحفظ الملف الطبي.',
            'method' => 'written',
            'given_at' => '2026-11-22 10:00',
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('consent_records', [
            'patient_id' => $patient->id,
            'type' => 'data_processing',
            'recorded_by' => $user->id,
        ]);
    }

    public function test_medical_alerts_and_national_id_hidden_from_non_clinical(): void
    {
        $owner = User::factory()->create();
        $receptionist = User::factory()->receptionist()->create(['clinic_id' => $owner->clinic_id]);
        $patient = Patient::factory()->create([
            'clinic_id' => $owner->clinic_id,
            'medical_alerts' => ['حساسية بنسلين'],
            'national_id' => '29901011234567',
        ]);

        $this->actingAs($receptionist)->get("/patients/{$patient->id}")
            ->assertInertia(fn ($page) => $page
                ->where('patient.medical_alerts', null)
                ->where('patient.national_id', null));

        $this->actingAs($owner)->get("/patients/{$patient->id}")
            ->assertInertia(fn ($page) => $page
                ->where('patient.medical_alerts.0', 'حساسية بنسلين'));
    }

    public function test_national_id_is_encrypted_at_rest(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create([
            'clinic_id' => $user->clinic_id, 'national_id' => '29901011234567',
        ]);

        $raw = \DB::table('patients')->where('id', $patient->id)->value('national_id');
        $this->assertStringNotContainsString('29901011234567', (string) $raw);
        $this->assertSame('29901011234567', $patient->fresh()->national_id);
    }

    public function test_patient_changes_are_audited(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['clinic_id' => $user->clinic_id, 'notes' => 'x']);

        $this->actingAs($user)->put("/patients/{$patient->id}", [
            'full_name' => $patient->full_name, 'phone' => '01099998888', 'notes' => 'y',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'updated',
            'auditable_type' => Patient::class,
            'auditable_id' => $patient->id,
        ]);
    }

    public function test_only_owner_can_delete_patient(): void
    {
        $owner = User::factory()->create();
        $dentist = User::factory()->dentist()->create(['clinic_id' => $owner->clinic_id]);
        $patient = Patient::factory()->create(['clinic_id' => $owner->clinic_id]);

        $this->actingAs($dentist)->delete("/patients/{$patient->id}")->assertForbidden();
        $this->actingAs($owner)->delete("/patients/{$patient->id}")->assertRedirect();
        $this->assertSoftDeleted('patients', ['id' => $patient->id]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_view_other_clinic_patient(): void
    {
        $userA = User::factory()->create();
        $patientB = Patient::factory()->create(); // different clinic

        $this->actingAs($userA)->get("/patients/{$patientB->id}")->assertNotFound();
    }

    public function test_patient_index_only_shows_own_clinic(): void
    {
        $userA = User::factory()->create();
        Patient::factory()->create(['clinic_id' => $userA->clinic_id, 'full_name' => 'مريض عيادتي']);
        Patient::factory()->create(['full_name' => 'مريض عيادة أخرى']);

        $this->actingAs($userA)->get('/patients')->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('patients.total', 1));
    }

    public function test_user_cannot_view_other_clinic_invoice(): void
    {
        $userA = User::factory()->create();
        $invoiceB = Invoice::factory()->create();

        $this->actingAs($userA)->get("/invoices/{$invoiceB->id}")->assertNotFound();
    }

    public function test_creating_patient_stamps_current_clinic(): void
    {
        $userA = User::factory()->create();
        Branch::factory()->create(['clinic_id' => $userA->clinic_id]);

        $this->actingAs($userA)->post('/patients', [
            'full_name' => 'مريض جديد', 'phone' => '01012345678',
        ])->assertRedirect();

        $this->assertDatabaseHas('patients', [
            'clinic_id' => $userA->clinic_id,
            'full_name' => 'مريض جديد',
        ]);
    }
}

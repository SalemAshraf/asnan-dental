<?php

namespace Tests\Feature;

use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsuranceTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_and_claim_lifecycle(): void
    {
        $user = User::factory()->create();
        $clinicId = $user->clinic_id;
        $patient = Patient::factory()->create(['clinic_id' => $clinicId]);

        $this->actingAs($user)->post('/insurance/companies', [
            'name' => 'شركة التأمين التجريبية', 'phone' => '01000000000',
        ])->assertRedirect();
        $company = InsuranceCompany::first();
        $this->assertSame($clinicId, $company->clinic_id);

        $this->actingAs($user)->post('/insurance/claims', [
            'patient_id' => $patient->id,
            'insurance_company_id' => $company->id,
            'amount' => '1500.50',
            'submitted_on' => now()->toDateString(),
        ])->assertRedirect();
        $claim = InsuranceClaim::first();
        $this->assertSame($clinicId, $claim->clinic_id);
        $this->assertSame(150050, $claim->amount);
        $this->assertSame('submitted', $claim->status);

        $this->actingAs($user)->patch("/insurance/claims/{$claim->id}", ['status' => 'approved'])
            ->assertRedirect();
        $this->assertSame('approved', $claim->fresh()->status);
        $this->assertNotNull($claim->fresh()->decided_on);

        $this->actingAs($user)->patch("/insurance/claims/{$claim->id}", ['status' => 'paid'])
            ->assertRedirect();
        $this->assertSame('paid', $claim->fresh()->status);
    }

    public function test_claim_rejects_cross_tenant_company_and_patient(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreignCompany = InsuranceCompany::create(['clinic_id' => $other->clinic_id, 'name' => 'أجنبية']);
        $foreignPatient = Patient::factory()->create(['clinic_id' => $other->clinic_id]);
        $ownPatient = Patient::factory()->create(['clinic_id' => $user->clinic_id]);
        $ownCompany = InsuranceCompany::create(['clinic_id' => $user->clinic_id, 'name' => 'خاصة']);

        $this->actingAs($user)->post('/insurance/claims', [
            'patient_id' => $foreignPatient->id,
            'insurance_company_id' => $ownCompany->id,
            'amount' => '10', 'submitted_on' => now()->toDateString(),
        ])->assertNotFound();

        $this->actingAs($user)->post('/insurance/claims', [
            'patient_id' => $ownPatient->id,
            'insurance_company_id' => $foreignCompany->id,
            'amount' => '10', 'submitted_on' => now()->toDateString(),
        ])->assertNotFound();

        $this->assertSame(0, InsuranceClaim::count());
    }

    public function test_claim_update_is_tenant_scoped(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $company = InsuranceCompany::create(['clinic_id' => $other->clinic_id, 'name' => 'أجنبية']);
        $patient = Patient::factory()->create(['clinic_id' => $other->clinic_id]);
        $claim = InsuranceClaim::create([
            'clinic_id' => $other->clinic_id, 'patient_id' => $patient->id,
            'insurance_company_id' => $company->id, 'amount' => 1000,
            'status' => 'submitted', 'submitted_on' => now()->toDateString(),
        ]);

        $this->actingAs($user)->patch("/insurance/claims/{$claim->id}", ['status' => 'paid'])
            ->assertNotFound();
        $this->assertSame('submitted', $claim->fresh()->status);
    }
}

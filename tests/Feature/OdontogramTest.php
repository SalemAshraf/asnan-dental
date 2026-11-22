<?php

namespace Tests\Feature;

use App\Models\OdontogramEntry;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OdontogramTest extends TestCase
{
    use RefreshDatabase;

    public function test_dentist_can_upsert_tooth_condition_and_update_it(): void
    {
        $dentist = User::factory()->dentist()->create();
        $patient = Patient::factory()->create(['clinic_id' => $dentist->clinic_id]);

        $this->actingAs($dentist)->post("/patients/{$patient->id}/odontogram", [
            'tooth_number' => 16, 'condition' => 'caries', 'surface' => 'O',
        ])->assertSessionDoesntHaveErrors();

        $this->actingAs($dentist)->post("/patients/{$patient->id}/odontogram", [
            'tooth_number' => 16, 'condition' => 'filling', 'surface' => 'O',
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame(1, OdontogramEntry::count());
        $this->assertSame('filling', OdontogramEntry::first()->condition);
    }

    public function test_invalid_tooth_rejected(): void
    {
        $dentist = User::factory()->dentist()->create();
        $patient = Patient::factory()->create(['clinic_id' => $dentist->clinic_id]);

        $this->actingAs($dentist)->post("/patients/{$patient->id}/odontogram", [
            'tooth_number' => 19, 'condition' => 'caries',
        ])->assertSessionHasErrors('tooth_number');
    }

    public function test_receptionist_cannot_edit_chart(): void
    {
        $receptionist = User::factory()->receptionist()->create();
        $patient = Patient::factory()->create(['clinic_id' => $receptionist->clinic_id]);

        $this->actingAs($receptionist)->post("/patients/{$patient->id}/odontogram", [
            'tooth_number' => 16, 'condition' => 'caries',
        ])->assertForbidden();
    }
}

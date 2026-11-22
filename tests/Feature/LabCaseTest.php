<?php

namespace Tests\Feature;

use App\Models\LabCase;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_create_edit_and_advance_lab_case(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['clinic_id' => $user->clinic_id]);

        $this->actingAs($user)->post('/lab-cases', [
            'patient_id' => $patient->id, 'lab_name' => 'معمل النور', 'work_type' => 'crown',
            'shade' => 'A2', 'sent_on' => '2026-11-20', 'due_on' => '2026-11-27', 'cost' => '900',
        ])->assertRedirect();

        $case = LabCase::first();
        $this->assertNotNull($case);
        $this->assertSame($user->clinic_id, $case->clinic_id);
        $this->assertSame('sent', $case->status);
        $this->assertSame(90000, $case->cost_minor);

        $this->actingAs($user)->put("/lab-cases/{$case->id}", [
            'patient_id' => $patient->id, 'lab_name' => 'معمل النور', 'work_type' => 'crown',
            'shade' => 'A3', 'sent_on' => '2026-11-20', 'due_on' => '2026-11-28', 'cost' => '950',
        ])->assertRedirect();
        $this->assertSame('A3', $case->fresh()->shade);
        $this->assertSame(95000, $case->fresh()->cost_minor);

        foreach (['in_progress', 'received', 'fitted'] as $status) {
            $this->actingAs($user)->post("/lab-cases/{$case->id}/transition", ['status' => $status])->assertRedirect();
            $this->assertSame($status, $case->fresh()->status);
        }
    }

    public function test_invalid_status_rejected(): void
    {
        $user = User::factory()->create();
        $case = LabCase::create([
            'clinic_id' => $user->clinic_id, 'lab_name' => 'معمل', 'work_type' => 'crown', 'sent_on' => '2026-11-20',
        ]);

        $this->actingAs($user)->post("/lab-cases/{$case->id}/transition", ['status' => 'bogus'])->assertSessionHasErrors('status');
        $this->assertSame('sent', $case->fresh()->status);
    }

    public function test_only_owner_can_delete_lab_case(): void
    {
        $owner = User::factory()->create();
        $receptionist = User::factory()->receptionist()->create(['clinic_id' => $owner->clinic_id]);
        $case = LabCase::create([
            'clinic_id' => $owner->clinic_id, 'lab_name' => 'معمل', 'work_type' => 'crown', 'sent_on' => '2026-11-20',
        ]);

        $this->actingAs($receptionist)->delete("/lab-cases/{$case->id}")->assertForbidden();
        $this->actingAs($owner)->delete("/lab-cases/{$case->id}")->assertRedirect();
        $this->assertDatabaseMissing('lab_cases', ['id' => $case->id]);
    }

    public function test_lab_cases_are_tenant_scoped(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreign = LabCase::create([
            'clinic_id' => $other->clinic_id, 'lab_name' => 'معمل', 'work_type' => 'crown', 'sent_on' => '2026-11-20',
        ]);

        $this->actingAs($user)->post("/lab-cases/{$foreign->id}/transition", ['status' => 'received'])->assertNotFound();
        $this->assertSame('sent', $foreign->fresh()->status);
    }
}

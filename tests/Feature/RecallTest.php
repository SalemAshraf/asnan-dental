<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Recall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecallTest extends TestCase
{
    use RefreshDatabase;

    public function test_recall_index_requires_authentication(): void
    {
        $this->get('/recalls')->assertRedirect();
    }

    public function test_recalls_are_scoped_to_clinic(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['clinic_id' => $user->clinic_id]);
        Recall::create(['clinic_id' => $user->clinic_id, 'patient_id' => $patient->id, 'due_date' => now()->toDateString(), 'status' => 'pending']);

        $otherClinic = Clinic::factory()->create();
        $otherPatient = Patient::factory()->create(['clinic_id' => $otherClinic->id]);
        Recall::create(['clinic_id' => $otherClinic->id, 'patient_id' => $otherPatient->id, 'due_date' => now()->toDateString(), 'status' => 'pending']);

        $this->actingAs($user)->get('/recalls')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('pending', 1));
    }

    public function test_user_can_create_and_complete_recall(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['clinic_id' => $user->clinic_id]);

        $this->actingAs($user)->post('/recalls', [
            'patient_id' => $patient->id,
            'due_date' => now()->addWeek()->toDateString(),
            'reason' => 'مراجعة دورية',
        ])->assertRedirect();

        $recall = Recall::sole();
        $this->assertSame('pending', $recall->status);
        $this->assertSame($user->clinic_id, $recall->clinic_id);

        $this->actingAs($user)->post("/recalls/{$recall->id}/transition", ["status" => "done"])->assertRedirect();
        $recall->refresh();
        $this->assertSame('done', $recall->status);
        $this->assertNotNull($recall->done_at);
    }

    public function test_user_cannot_transition_other_clinic_recall(): void
    {
        $user = User::factory()->create();
        $otherClinic = Clinic::factory()->create();
        $otherPatient = Patient::factory()->create(['clinic_id' => $otherClinic->id]);
        $foreign = Recall::create(['clinic_id' => $otherClinic->id, 'patient_id' => $otherPatient->id, 'due_date' => now()->toDateString(), 'status' => 'pending']);

        $this->actingAs($user)->post("/recalls/{$foreign->id}/transition", ["status" => "done"])->assertNotFound();
    }
}

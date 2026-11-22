<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentOverlapTest extends TestCase
{
    use RefreshDatabase;

    private array $ctx;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $branch = Branch::factory()->create(['clinic_id' => $user->clinic_id]);
        $dentist = User::factory()->dentist()->create(['clinic_id' => $user->clinic_id, 'branch_id' => $branch->id]);
        $patient = Patient::factory()->create(['clinic_id' => $user->clinic_id, 'branch_id' => $branch->id]);
        $this->ctx = compact('user', 'branch', 'dentist', 'patient');
    }

    private function payload(array $over = []): array
    {
        return [
            'branch_id' => $this->ctx['branch']->id,
            'patient_id' => $this->ctx['patient']->id,
            'dentist_id' => $this->ctx['dentist']->id,
            'chair' => 'كرسي 1',
            'starts_at' => '2026-11-25 10:00:00',
            'ends_at' => '2026-11-25 10:30:00',
            ...$over,
        ];
    }

    public function test_create_appointment(): void
    {
        $this->actingAs($this->ctx['user'])->post('/appointments', $this->payload())->assertRedirect();
        $this->assertDatabaseHas('appointments', ['status' => 'scheduled']);
    }

    public function test_same_dentist_overlap_rejected(): void
    {
        $this->actingAs($this->ctx['user'])->post('/appointments', $this->payload());
        $this->actingAs($this->ctx['user'])->post('/appointments', $this->payload([
            'chair' => 'كرسي 2', 'starts_at' => '2026-11-25 10:15:00', 'ends_at' => '2026-11-25 10:45:00',
        ]))->assertSessionHasErrors('starts_at');
    }

    public function test_same_chair_overlap_rejected(): void
    {
        $dentist2 = User::factory()->dentist()->create(['clinic_id' => $this->ctx['user']->clinic_id]);
        $this->actingAs($this->ctx['user'])->post('/appointments', $this->payload());
        $this->actingAs($this->ctx['user'])->post('/appointments', $this->payload([
            'dentist_id' => $dentist2->id, 'starts_at' => '2026-11-25 10:15:00', 'ends_at' => '2026-11-25 10:45:00',
        ]))->assertSessionHasErrors('chair');
    }

    public function test_adjacent_appointments_allowed(): void
    {
        $this->actingAs($this->ctx['user'])->post('/appointments', $this->payload());
        $this->actingAs($this->ctx['user'])->post('/appointments', $this->payload([
            'starts_at' => '2026-11-25 10:30:00', 'ends_at' => '2026-11-25 11:00:00',
        ]))->assertSessionDoesntHaveErrors();
    }

    public function test_cancelled_appointment_frees_the_slot(): void
    {
        $this->actingAs($this->ctx['user'])->post('/appointments', $this->payload());
        $appointment = Appointment::first();
        $this->actingAs($this->ctx['user'])->post("/appointments/{$appointment->id}/transition", ['status' => 'cancelled']);
        $this->actingAs($this->ctx['user'])->post('/appointments', $this->payload())
            ->assertSessionDoesntHaveErrors();
    }
}

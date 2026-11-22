<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Patient;
use App\Models\ReminderMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $this->postJson('/api/v1/login', [
            'email' => $user->email, 'password' => 'password', 'device_name' => 'test',
        ])->assertOk()->json('token');
    }

    public function test_login_issues_token_and_me_returns_clinic_payload(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $this->withToken($token)->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.clinic.id', $user->clinic_id);
    }

    public function test_login_rejects_wrong_password_and_inactive_user(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/login', [
            'email' => $user->email, 'password' => 'wrong-password', 'device_name' => 'test',
        ])->assertStatus(422);

        $user->update(['is_active' => false]);
        $this->postJson('/api/v1/login', [
            'email' => $user->email, 'password' => 'password', 'device_name' => 'test',
        ])->assertForbidden();
    }

    public function test_appointments_are_tenant_scoped_and_dated(): void
    {
        $user = User::factory()->create();
        $clinicId = $user->clinic_id;
        $branch = Branch::factory()->create(['clinic_id' => $clinicId]);
        $patient = Patient::factory()->create(['clinic_id' => $clinicId]);
        Appointment::factory()->create([
            'clinic_id' => $clinicId, 'branch_id' => $branch->id, 'patient_id' => $patient->id,
            'starts_at' => now()->setTime(10, 0), 'ends_at' => now()->setTime(10, 30), 'status' => 'scheduled',
        ]);
        Appointment::factory()->create([
            'clinic_id' => $clinicId, 'branch_id' => $branch->id, 'patient_id' => $patient->id,
            'starts_at' => now()->addDays(3)->setTime(10, 0), 'ends_at' => now()->addDays(3)->setTime(10, 30), 'status' => 'scheduled',
        ]);
        // Foreign clinic appointment today must never leak.
        $other = User::factory()->create();
        Appointment::factory()->create([
            'starts_at' => now()->setTime(11, 0), 'ends_at' => now()->setTime(11, 30), 'status' => 'scheduled',
        ]);

        $token = $this->tokenFor($user);
        $this->withToken($token)->getJson('/api/v1/appointments')
            ->assertOk()
            ->assertJsonCount(1, 'appointments')
            ->assertJsonPath('appointments.0.patient.id', $patient->id);
        $this->withToken($token)->getJson('/api/v1/appointments?date='.now()->addDays(3)->toDateString())
            ->assertOk()->assertJsonCount(1, 'appointments');
    }

    public function test_patient_search_and_reminder_mark_sent(): void
    {
        $user = User::factory()->create();
        $clinicId = $user->clinic_id;
        $patient = Patient::factory()->create(['clinic_id' => $clinicId, 'full_name' => 'مريض الاختبار الفريد']);
        Patient::factory()->create(['full_name' => 'مريض الاختبار الفريد']); // other clinic

        $reminder = ReminderMessage::create([
            'clinic_id' => $clinicId, 'patient_id' => $patient->id,
            'kind' => 'recall', 'scheduled_for' => now()->toDateString(), 'dedupe_key' => 'recall:8001',
        ]);
        $foreign = ReminderMessage::create([
            'clinic_id' => Patient::latest('id')->first()->clinic_id, 'patient_id' => Patient::latest('id')->first()->id,
            'kind' => 'recall', 'scheduled_for' => now()->toDateString(), 'dedupe_key' => 'recall:8002',
        ]);

        $token = $this->tokenFor($user);

        $this->withToken($token)->getJson('/api/v1/patients?q='.urlencode('الفريد'))
            ->assertOk()->assertJsonCount(1, 'patients');

        $this->withToken($token)->getJson('/api/v1/reminders')
            ->assertOk()->assertJsonCount(1, 'reminders');

        $this->withToken($token)->postJson("/api/v1/reminders/{$reminder->id}/sent")
            ->assertOk()->assertJsonPath('status', 'sent');
        $this->assertSame('sent', $reminder->fresh()->status);

        // Cross-tenant reminder: not found.
        $this->withToken($token)->postJson("/api/v1/reminders/{$foreign->id}/sent")->assertNotFound();

        $this->withToken($token)->postJson('/api/v1/logout')->assertOk();
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
        $this->getJson('/api/v1/appointments')->assertUnauthorized();
    }
}

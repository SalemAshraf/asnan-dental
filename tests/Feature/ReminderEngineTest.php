<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Patient;
use App\Models\Recall;
use App\Models\ReminderMessage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReminderEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makeClinicFixtures(): array
    {
        Carbon::setTestNow(Carbon::parse('2026-11-22 12:00:00'));
        $user = User::factory()->create();
        $branch = Branch::factory()->create(['clinic_id' => $user->clinic_id]);
        $patient = Patient::factory()->create(['clinic_id' => $user->clinic_id]);

        return [$user, $branch, $patient];
    }

    public function test_command_queues_due_recalls_and_tomorrows_appointments_only(): void
    {
        [$user, $branch, $patient] = $this->makeClinicFixtures();

        $dueRecall = Recall::create([
            'clinic_id' => $user->clinic_id, 'patient_id' => $patient->id,
            'due_date' => '2026-11-23', 'status' => 'pending', 'reason' => 'مراجعة',
        ]);
        $doneRecall = Recall::create([
            'clinic_id' => $user->clinic_id, 'patient_id' => $patient->id,
            'due_date' => '2026-11-22', 'status' => 'done', 'reason' => 'مراجعة',
        ]);
        $standing = Appointment::factory()->create([
            'clinic_id' => $user->clinic_id, 'branch_id' => $branch->id, 'patient_id' => $patient->id,
            'starts_at' => '2026-11-23 10:00:00', 'ends_at' => '2026-11-23 10:30:00', 'status' => 'confirmed',
        ]);
        $cancelled = Appointment::factory()->create([
            'clinic_id' => $user->clinic_id, 'branch_id' => $branch->id, 'patient_id' => $patient->id,
            'starts_at' => '2026-11-23 11:00:00', 'ends_at' => '2026-11-23 11:30:00', 'status' => 'cancelled',
        ]);
        $laterWeek = Appointment::factory()->create([
            'clinic_id' => $user->clinic_id, 'branch_id' => $branch->id, 'patient_id' => $patient->id,
            'starts_at' => '2026-11-25 10:00:00', 'ends_at' => '2026-11-25 10:30:00', 'status' => 'scheduled',
        ]);

        $this->artisan('reminders:queue')->assertSuccessful();

        $this->assertDatabaseHas('reminder_messages', [
            'clinic_id' => $user->clinic_id, 'recall_id' => $dueRecall->id,
            'kind' => 'recall', 'status' => 'queued', 'channel' => 'whatsapp_manual',
            'scheduled_for' => '2026-11-23', 'dedupe_key' => 'recall:'.$dueRecall->id,
        ]);
        $this->assertDatabaseHas('reminder_messages', [
            'appointment_id' => $standing->id, 'kind' => 'appointment', 'status' => 'queued',
        ]);
        $this->assertDatabaseMissing('reminder_messages', ['recall_id' => $doneRecall->id]);
        $this->assertDatabaseMissing('reminder_messages', ['appointment_id' => $cancelled->id]);
        $this->assertDatabaseMissing('reminder_messages', ['appointment_id' => $laterWeek->id]);
        $this->assertSame(2, ReminderMessage::count());
    }

    public function test_command_is_idempotent(): void
    {
        [$user, $branch, $patient] = $this->makeClinicFixtures();
        Recall::create([
            'clinic_id' => $user->clinic_id, 'patient_id' => $patient->id,
            'due_date' => '2026-11-22', 'status' => 'pending', 'reason' => 'مراجعة',
        ]);

        $this->artisan('reminders:queue')->assertSuccessful();
        $this->artisan('reminders:queue')->assertSuccessful();

        $this->assertSame(1, ReminderMessage::count());
    }

    public function test_staff_can_mark_reminder_sent_and_skipped(): void
    {
        [$user, , $patient] = $this->makeClinicFixtures();

        $sent = ReminderMessage::create([
            'clinic_id' => $user->clinic_id, 'patient_id' => $patient->id,
            'kind' => 'recall', 'scheduled_for' => '2026-11-22', 'dedupe_key' => 'recall:9001',
        ]);
        $skipped = ReminderMessage::create([
            'clinic_id' => $user->clinic_id, 'patient_id' => $patient->id,
            'kind' => 'appointment', 'scheduled_for' => '2026-11-23', 'dedupe_key' => 'appt:9002',
        ]);

        $this->actingAs($user)->patch("/reminders/{$sent->id}", ['status' => 'sent'])->assertRedirect();
        $sent->refresh();
        $this->assertSame('sent', $sent->status);
        $this->assertSame($user->id, $sent->sent_by);
        $this->assertNotNull($sent->sent_at);

        $this->actingAs($user)->patch("/reminders/{$skipped->id}", ['status' => 'skipped'])->assertRedirect();
        $skipped->refresh();
        $this->assertSame('skipped', $skipped->status);
        $this->assertNull($skipped->sent_at);
        $this->assertNull($skipped->sent_by);
    }

    public function test_reminder_update_is_tenant_scoped(): void
    {
        [$user, , $patient] = $this->makeClinicFixtures();
        $other = User::factory()->create();
        $otherPatient = Patient::factory()->create(['clinic_id' => $other->clinic_id]);
        $foreign = ReminderMessage::create([
            'clinic_id' => $other->clinic_id, 'patient_id' => $otherPatient->id,
            'kind' => 'recall', 'scheduled_for' => '2026-11-22', 'dedupe_key' => 'recall:9003',
        ]);

        $this->actingAs($user)->patch("/reminders/{$foreign->id}", ['status' => 'sent'])->assertNotFound();
        $this->assertSame('queued', $foreign->fresh()->status);
    }
}

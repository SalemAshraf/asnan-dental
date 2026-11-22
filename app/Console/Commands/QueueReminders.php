<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Recall;
use App\Models\ReminderMessage;
use Illuminate\Console\Command;

class QueueReminders extends Command
{
    protected $signature = 'reminders:queue';

    protected $description = 'Queue due recall and next-day appointment reminders for manual one-tap sending (no automated sends).';

    public function handle(): int
    {
        $created = 0;

        Clinic::query()->select('id', 'timezone')->chunk(50, function ($clinics) use (&$created) {
            foreach ($clinics as $clinic) {
                $tz = $clinic->timezone ?: config('app.timezone');
                $today = now($tz)->toDateString();
                $tomorrow = now($tz)->addDay()->toDateString();

                // Pending recalls due tomorrow or earlier (including overdue).
                Recall::query()
                    ->where('clinic_id', $clinic->id)
                    ->where('status', 'pending')
                    ->whereDate('due_date', '<=', $tomorrow)
                    ->select('id', 'clinic_id', 'patient_id', 'due_date')
                    ->chunkById(200, function ($recalls) use (&$created) {
                        foreach ($recalls as $recall) {
                            $created += ReminderMessage::insertOrIgnore([[
                                'clinic_id' => $recall->clinic_id,
                                'patient_id' => $recall->patient_id,
                                'recall_id' => $recall->id,
                                'appointment_id' => null,
                                'kind' => 'recall',
                                'channel' => 'whatsapp_manual',
                                'scheduled_for' => $recall->due_date->toDateString(),
                                'status' => 'queued',
                                'dedupe_key' => 'recall:'.$recall->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]]);
                        }
                    });

                // Tomorrow's appointments that still stand.
                Appointment::query()
                    ->where('clinic_id', $clinic->id)
                    ->whereIn('status', ['scheduled', 'confirmed'])
                    ->whereDate('starts_at', $tomorrow)
                    ->select('id', 'clinic_id', 'patient_id', 'starts_at')
                    ->chunkById(200, function ($appointments) use (&$created) {
                        foreach ($appointments as $appointment) {
                            $created += ReminderMessage::insertOrIgnore([[
                                'clinic_id' => $appointment->clinic_id,
                                'patient_id' => $appointment->patient_id,
                                'recall_id' => null,
                                'appointment_id' => $appointment->id,
                                'kind' => 'appointment',
                                'channel' => 'whatsapp_manual',
                                'scheduled_for' => $appointment->starts_at->toDateString(),
                                'status' => 'queued',
                                'dedupe_key' => 'appt:'.$appointment->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]]);
                        }
                    });
            }
        });

        $this->info("Queued {$created} reminder(s).");

        return self::SUCCESS;
    }
}

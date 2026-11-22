<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $branch = Branch::factory()->create();
        $start = now()->addDay()->setTime(10, 0);

        return [
            'clinic_id' => $branch->clinic_id,
            'branch_id' => $branch->id,
            'patient_id' => Patient::factory()->state(['clinic_id' => $branch->clinic_id]),
            'dentist_id' => User::factory()->dentist()->state(['clinic_id' => $branch->clinic_id]),
            'chair' => 'كرسي 1',
            'starts_at' => $start,
            'ends_at' => (clone $start)->addMinutes(30),
            'status' => 'scheduled',
        ];
    }
}

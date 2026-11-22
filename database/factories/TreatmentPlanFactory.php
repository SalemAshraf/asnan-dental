<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\TreatmentPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TreatmentPlanFactory extends Factory
{
    protected $model = TreatmentPlan::class;

    public function definition(): array
    {
        $patient = Patient::factory()->create();

        return [
            'clinic_id' => $patient->clinic_id,
            'patient_id' => $patient->id,
            'dentist_id' => User::factory()->dentist()->state(['clinic_id' => $patient->clinic_id]),
            'title' => 'خطة علاج',
            'status' => 'draft',
            'total_minor' => 0,
        ];
    }
}

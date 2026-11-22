<?php

namespace Database\Factories;

use App\Models\Clinic;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        return [
            'clinic_id' => Clinic::factory(),
            'file_no' => $this->faker->unique()->numberBetween(1, 999999),
            'full_name' => $this->faker->name(),
            'phone' => '01'.$this->faker->numerify('#########'),
            'medical_alerts' => [],
        ];
    }
}

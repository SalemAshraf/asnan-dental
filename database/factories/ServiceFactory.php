<?php

namespace Database\Factories;

use App\Models\Clinic;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'clinic_id' => Clinic::factory(),
            'code' => strtoupper($this->faker->unique()->bothify('SV-###')),
            'name_ar' => 'خدمة '.$this->faker->word(),
            'category' => 'restorative',
            'default_price_minor' => 50000,
            'duration_min' => 30,
            'is_active' => true,
        ];
    }
}

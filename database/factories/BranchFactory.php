<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Clinic;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'clinic_id' => Clinic::factory(),
            'name' => 'فرع '.$this->faker->city(),
            'is_active' => true,
        ];
    }
}

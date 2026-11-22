<?php

namespace Database\Factories;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'clinic_id' => Clinic::factory(),
            'branch_id' => null,
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => 'password',
            'role' => User::ROLE_OWNER,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function dentist(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_DENTIST]);
    }

    public function receptionist(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_RECEPTIONIST]);
    }
}

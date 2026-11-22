<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_require_owner_role(): void
    {
        $receptionist = User::factory()->create(['role' => User::ROLE_RECEPTIONIST]);
        $dentist = User::factory()->create(['role' => User::ROLE_DENTIST]);

        $this->actingAs($receptionist)->get('/reports')->assertForbidden();
        $this->actingAs($dentist)->get('/reports')->assertForbidden();
    }

    public function test_owner_can_view_reports(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);

        $this->actingAs($owner)->get('/reports')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('cards')
                ->has('daily')
                ->has('perDentist')
                ->has('topServices'));
    }
}

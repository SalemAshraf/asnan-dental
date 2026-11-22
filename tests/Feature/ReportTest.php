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

    public function test_export_streams_csv_for_owner(): void
    {
        $owner = \App\Models\User::factory()->create();

        $res = $this->actingAs($owner)->get('/reports/export');
        $res->assertOk();
        $this->assertStringContainsString('text/csv', $res->headers->get('content-type'));
        $content = $res->streamedContent();
        $this->assertStringContainsString('summary', $content);
        $this->assertStringContainsString('payments_by_method', $content);
    }

    public function test_export_forbidden_for_non_owner(): void
    {
        $owner = \App\Models\User::factory()->create();
        $receptionist = \App\Models\User::factory()->receptionist()->create(['clinic_id' => $owner->clinic_id]);

        $this->actingAs($receptionist)->get('/reports/export')->assertForbidden();
    }

    public function test_export_respects_range(): void
    {
        $owner = \App\Models\User::factory()->create();

        $res = $this->actingAs($owner)->get('/reports/export?from=2026-11-01&to=2026-11-30');
        $this->assertStringContainsString('2026-11-01', $res->streamedContent());
    }
}

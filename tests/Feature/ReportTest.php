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

    public function test_export_neutralizes_spreadsheet_formulas(): void
    {
        $owner = \App\Models\User::factory()->create();
        $branch = \App\Models\Branch::factory()->create(['clinic_id' => $owner->clinic_id]);
        $evil = \App\Models\User::factory()->dentist()->create(['clinic_id' => $owner->clinic_id, 'name' => '=1+1']);
        \App\Models\Patient::factory()->create(['clinic_id' => $owner->clinic_id, 'branch_id' => $branch->id]);
        \App\Models\Appointment::create([
            'clinic_id' => $owner->clinic_id, 'branch_id' => $branch->id,
            'patient_id' => \App\Models\Patient::first()->id, 'dentist_id' => $evil->id,
            'starts_at' => now()->utc(), 'ends_at' => now()->addMinutes(30)->utc(), 'status' => 'scheduled',
        ]);

        $content = $this->actingAs($owner)->get('/reports/export')->streamedContent();
        $this->assertStringContainsString("'=1+1", $content);
        $this->assertStringNotContainsString(',=1+1', $content);
    }

    public function test_export_excludes_other_clinics_numbers(): void
    {
        $owner = \App\Models\User::factory()->create();
        $otherClinic = \App\Models\Clinic::factory()->create();
        $otherPatient = \App\Models\Patient::factory()->create(['clinic_id' => $otherClinic->id]);
        $otherInvoice = \App\Models\Invoice::factory()->create([
            'clinic_id' => $otherClinic->id, 'patient_id' => $otherPatient->id,
            'status' => 'paid', 'total_minor' => 9999900, 'paid_minor' => 9999900,
        ]);

        $content = $this->actingAs($owner)->get('/reports/export')->streamedContent();
        $this->assertStringNotContainsString('99999.00', $content);
    }

    public function test_export_respects_range(): void
    {
        $owner = \App\Models\User::factory()->create();

        $res = $this->actingAs($owner)->get('/reports/export?from=2026-11-01&to=2026-11-30');
        $this->assertStringContainsString('2026-11-01', $res->streamedContent());
    }
}

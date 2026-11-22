<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentWeekTest extends TestCase
{
    use RefreshDatabase;

    public function test_week_view_returns_seven_days_with_appointments(): void
    {
        $user = User::factory()->create();
        $branch = Branch::factory()->create(['clinic_id' => $user->clinic_id]);
        $patient = Patient::factory()->create(['clinic_id' => $user->clinic_id, 'branch_id' => $branch->id]);

        $tz = $user->clinic->timezone ?? config('app.timezone');
        $start = Carbon::parse('next saturday', $tz)->startOfWeek(Carbon::SATURDAY);
        Appointment::create([
            'clinic_id' => $user->clinic_id, 'branch_id' => $branch->id,
            'patient_id' => $patient->id, 'dentist_id' => $user->id,
            'starts_at' => $start->copy()->addDays(2)->setTime(10, 0)->utc(),
            'ends_at' => $start->copy()->addDays(2)->setTime(10, 30)->utc(),
            'status' => 'scheduled',
        ]);

        $this->actingAs($user)->get('/appointments?view=week&day='.$start->toDateString())
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('view', 'week')
                ->has('week', 7)
                ->has('week.2.appointments', 1));
    }
}

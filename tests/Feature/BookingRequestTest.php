<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\BookingRequest;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingRequestTest extends TestCase
{
    use RefreshDatabase;

    private function makeRequest(Clinic $clinic, array $over = []): BookingRequest
    {
        return BookingRequest::create(array_merge([
            'clinic_id' => $clinic->id, 'name' => 'زائر جديد', 'phone' => '01000000099',
            'status' => BookingRequest::STATUS_NEW,
        ], $over));
    }

    public function test_booking_page_404_when_disabled(): void
    {
        $clinic = Clinic::factory()->create(['booking_enabled' => false]);

        $this->get("/b/{$clinic->booking_token}")->assertNotFound();
    }

    public function test_booking_page_served_when_enabled(): void
    {
        $clinic = Clinic::factory()->create(['booking_enabled' => true]);
        Service::factory()->create(['clinic_id' => $clinic->id, 'name_ar' => 'تنظيف']);

        $this->get("/b/{$clinic->booking_token}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('clinicName', $clinic->name)
                ->has('services', 1));
    }

    public function test_public_submit_stores_request(): void
    {
        $clinic = Clinic::factory()->create(['booking_enabled' => true]);
        $service = Service::factory()->create(['clinic_id' => $clinic->id]);

        $this->post("/b/{$clinic->booking_token}", [
            'name' => 'زائر جديد', 'phone' => '01000000099',
            'service_id' => $service->id, 'preferred_date' => '2026-12-01',
            'preferred_time' => '6pm', 'notes' => '', 'website' => '',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('booking_requests', [
            'clinic_id' => $clinic->id, 'phone' => '01000000099', 'status' => 'new',
        ]);
    }

    public function test_honeypot_is_blocked_silently(): void
    {
        $clinic = Clinic::factory()->create(['booking_enabled' => true]);

        $this->post("/b/{$clinic->booking_token}", [
            'name' => 'Bot', 'phone' => '1', 'website' => 'spam',
        ]);

        $this->assertSame(0, BookingRequest::count());
    }

    public function test_rejects_service_of_another_clinic(): void
    {
        $clinic = Clinic::factory()->create(['booking_enabled' => true]);
        $foreign = Service::factory()->create(['clinic_id' => Clinic::factory()->create()->id]);

        $this->post("/b/{$clinic->booking_token}", [
            'name' => 'زائر', 'phone' => '01000000098', 'service_id' => $foreign->id, 'website' => '',
        ])->assertSessionHasErrors('service_id');

        $this->assertSame(0, BookingRequest::count());
    }

    public function test_accept_creates_patient_and_appointment(): void
    {
        $owner = User::factory()->create();
        $branch = \App\Models\Branch::factory()->create(['clinic_id' => $owner->clinic_id]);
        $request = $this->makeRequest($owner->clinic, ['name' => 'مريض جديد', 'phone' => '01055544433']);

        $this->actingAs($owner)->post("/booking-requests/{$request->id}/accept", [
            'branch_id' => $branch->id, 'dentist_id' => $owner->id,
            'date' => '2026-12-05', 'time' => '18:00',
        ])->assertSessionHasNoErrors();

        $patient = Patient::where('phone', '01055544433')->first();
        $this->assertNotNull($patient);

        $appointment = Appointment::where('patient_id', $patient->id)->first();
        $this->assertNotNull($appointment);
        $this->assertSame('confirmed', $appointment->status);
        $this->assertSame('online', $appointment->booked_via);
        $this->assertSame(BookingRequest::STATUS_CONFIRMED, $request->fresh()->status);
    }

    public function test_accept_reuses_patient_with_same_phone(): void
    {
        $owner = User::factory()->create();
        $branch = \App\Models\Branch::factory()->create(['clinic_id' => $owner->clinic_id]);
        $patient = Patient::factory()->create(['clinic_id' => $owner->clinic_id, 'phone' => '01055544432']);
        $request = $this->makeRequest($owner->clinic, ['phone' => '01055544432']);

        $this->actingAs($owner)->post("/booking-requests/{$request->id}/accept", [
            'branch_id' => $branch->id, 'dentist_id' => $owner->id,
            'date' => '2026-12-06', 'time' => '19:00',
        ]);

        $this->assertSame(1, Patient::where('phone', '01055544432')->count());
        $this->assertSame($patient->id, $request->fresh()->patient_id);
    }

    public function test_decline_only_marks_request(): void
    {
        $owner = User::factory()->create();
        $request = $this->makeRequest($owner->clinic);

        $this->actingAs($owner)->post("/booking-requests/{$request->id}/decline");

        $this->assertSame(BookingRequest::STATUS_DECLINED, $request->fresh()->status);
        $this->assertSame(0, Appointment::count());
    }

    public function test_cannot_accept_already_handled_request(): void
    {
        $owner = User::factory()->create();
        $branch = \App\Models\Branch::factory()->create(['clinic_id' => $owner->clinic_id]);
        $request = $this->makeRequest($owner->clinic, ['status' => BookingRequest::STATUS_CONFIRMED]);

        $this->actingAs($owner)->post("/booking-requests/{$request->id}/accept", [
            'branch_id' => $branch->id, 'dentist_id' => $owner->id,
            'date' => '2026-12-05', 'time' => '18:00',
        ])->assertForbidden();
    }

    public function test_cross_tenant_access_denied(): void
    {
        $owner = User::factory()->create();
        $foreign = $this->makeRequest(Clinic::factory()->create());

        $this->actingAs($owner)->post("/booking-requests/{$foreign->id}/decline")->assertNotFound();
        $branch = \App\Models\Branch::factory()->create(['clinic_id' => $owner->clinic_id]);
        $this->actingAs($owner)->post("/booking-requests/{$foreign->id}/accept", [
            'branch_id' => $branch->id, 'dentist_id' => $owner->id,
            'date' => '2026-12-05', 'time' => '18:00',
        ])->assertNotFound();
    }
}

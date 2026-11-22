<?php

namespace App\Http\Controllers;

use App\Models\BookingRequest;
use App\Models\Clinic;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Public, unauthenticated booking-request page for a clinic. A request is not an appointment: the clinic confirms it first. */
class PublicBookingController extends Controller
{
    private function clinicFromToken(string $token): Clinic
    {
        return Clinic::where('booking_token', $token)->where('booking_enabled', true)->firstOrFail();
    }

    public function form(Request $request, string $token)
    {
        $clinic = $this->clinicFromToken($token);

        // Per-clinic booking pages are reached by link, not search engines.
        $page = Inertia::render('Booking', [
            'clinicName' => $clinic->name,
            'token' => $token,
            'services' => $clinic->services()->where('is_active', true)->orderBy('name_ar')->get(['id', 'name_ar', 'name_en', 'duration_min', 'default_price_minor']),
            'currency' => $clinic->currency,
        ]);
        $response = $page->toResponse($request);
        $response->headers->set('X-Robots-Tag', 'noindex');

        return $response;
    }

    public function submit(Request $r, string $token)
    {
        $clinic = $this->clinicFromToken($token);
        if ($r->filled('website')) { // honeypot
            return back()->with('success', 'ok');
        }
        $d = $r->validate([
            'name' => 'required|string|max:160',
            'phone' => 'required|string|max:40',
            'service_id' => 'nullable|integer|exists:services,id',
            'preferred_date' => 'nullable|date|after_or_equal:today',
            'preferred_time' => 'nullable|string|max:10',
            'notes' => 'nullable|string|max:1000',
        ]);
        if (! empty($d['service_id']) && ! $clinic->services()->whereKey($d['service_id'])->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['service_id' => 'unknown service']);
        }
        BookingRequest::create([...$d, 'clinic_id' => $clinic->id]);

        return back()->with('success', 'ok');
    }
}

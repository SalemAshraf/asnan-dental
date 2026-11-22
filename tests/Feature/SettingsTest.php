<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_clinic_timezone_and_currency(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);

        $this->actingAs($owner)->put('/settings/clinic', [
            'name' => 'عيادة التجربة',
            'tax_rate_bp' => 0,
            'invoice_prefix' => 'INV',
            'timezone' => 'Asia/Riyadh',
            'currency' => 'sar',
        ])->assertRedirect();

        $owner->clinic->refresh();
        $this->assertSame('Asia/Riyadh', $owner->clinic->timezone);
        $this->assertSame('SAR', $owner->clinic->currency);
    }

    public function test_booking_toggle_persists(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->put('/settings/clinic', [
            'name' => 'عيادة التجربة', 'tax_rate_bp' => 0, 'invoice_prefix' => 'INV',
            'timezone' => 'Africa/Cairo', 'currency' => 'EGP', 'booking_enabled' => true,
        ])->assertSessionHasNoErrors();

        $this->assertTrue((bool) $owner->clinic->refresh()->booking_enabled);

        $this->actingAs($owner)->put('/settings/clinic', [
            'name' => 'عيادة التجربة', 'tax_rate_bp' => 0, 'invoice_prefix' => 'INV',
            'timezone' => 'Africa/Cairo', 'currency' => 'EGP', 'booking_enabled' => false,
        ])->assertSessionHasNoErrors();

        $this->assertFalse((bool) $owner->clinic->refresh()->booking_enabled);
    }

    public function test_invalid_timezone_rejected(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);

        $this->actingAs($owner)->put('/settings/clinic', [
            'name' => 'عيادة التجربة',
            'tax_rate_bp' => 0,
            'invoice_prefix' => 'INV',
            'timezone' => 'Mars/Olympus',
            'currency' => 'EGP',
        ])->assertSessionHasErrors('timezone');
    }

    public function test_settings_require_owner_role(): void
    {
        $receptionist = User::factory()->create(['role' => User::ROLE_RECEPTIONIST]);

        $this->actingAs($receptionist)->get('/settings')->assertForbidden();
    }
}

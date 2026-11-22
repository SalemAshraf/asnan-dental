<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Plan;
use App\Models\PlatformAdmin;
use App\Models\SalesInquiry;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesPlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_landing_and_auth_user_goes_to_dashboard(): void
    {
        $this->get('/')->assertOk();

        $user = User::factory()->create();
        $this->actingAs($user)->get('/')->assertRedirect('/dashboard');
    }

    public function test_register_creates_clinic_owner_and_trial(): void
    {
        $this->post('/register', [
            'name' => 'Dr Owner',
            'clinic_name' => 'Test Dental',
            'email' => 'owner@example.com',
            'password' => 'secret-password-1',
            'password_confirmation' => 'secret-password-1',
        ])->assertRedirect('/dashboard');

        $clinic = Clinic::where('name', 'Test Dental')->firstOrFail();
        $this->assertDatabaseHas('users', ['clinic_id' => $clinic->id, 'role' => 'owner', 'email' => 'owner@example.com']);
        // No trial duration is configured by default: the new account awaits manual activation.
        $sub = Subscription::where('clinic_id', $clinic->id)->firstOrFail();
        $this->assertSame('pending', $sub->status);
        $this->assertFalse($sub->trial_enforced);
        $this->assertNull($sub->trial_ends_at);
        $this->assertTrue($clinic->fresh()->isReadOnly());
    }

    public function test_trial_is_granted_only_when_configured(): void
    {
        config(['platform.trial_days' => 10]);

        $this->post('/register', [
            'name' => 'Dr Owner',
            'clinic_name' => 'Trial Dental',
            'email' => 'trial@example.com',
            'password' => 'secret-password-1',
            'password_confirmation' => 'secret-password-1',
        ])->assertRedirect('/dashboard');

        $clinic = Clinic::where('name', 'Trial Dental')->firstOrFail();
        $sub = Subscription::where('clinic_id', $clinic->id)->firstOrFail();
        $this->assertSame('trialing', $sub->status);
        $this->assertTrue($sub->trial_enforced);
        $this->assertNotNull($sub->trial_ends_at);
        $this->assertFalse($clinic->fresh()->isReadOnly());
    }

    public function test_pending_account_can_view_but_not_write(): void
    {
        $clinic = Clinic::factory()->create();
        Subscription::create(['clinic_id' => $clinic->id, 'status' => 'pending']);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);

        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->actingAs($user)->post('/services', [
            'name_ar' => 'حشو', 'category' => 'restorative', 'price' => 100, 'duration_min' => 30,
        ])->assertSessionHas('error', 'read_only');
        $this->assertDatabaseMissing('services', ['clinic_id' => $clinic->id, 'name_ar' => 'حشو']);

        // A read-only clinic can still reach the public inquiry form, and can still log out.
        $this->get('/pricing')->assertOk();
        $this->post('/pricing/inquiry', ['name' => 'Dr A', 'phone' => '01000000000'])->assertSessionHas('success');
        $this->actingAs($user)->post('/logout')->assertRedirect('/login');
    }

    public function test_expired_trial_is_read_only_but_can_still_view(): void
    {
        $clinic = Clinic::factory()->create();
        Subscription::create(['clinic_id' => $clinic->id, 'status' => 'trialing', 'trial_enforced' => true, 'trial_ends_at' => now()->subDay()]);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);

        $this->actingAs($user)->get('/dashboard')->assertOk();

        $this->actingAs($user)->post('/services', [
            'name_ar' => 'حشو', 'category' => 'restorative', 'price' => 100, 'duration_min' => 30,
        ])->assertSessionHas('error', 'read_only');

        $this->assertDatabaseMissing('services', ['clinic_id' => $clinic->id, 'name_ar' => 'حشو']);
    }

    public function test_activated_subscription_clears_read_only(): void
    {
        $clinic = Clinic::factory()->create();
        Subscription::create(['clinic_id' => $clinic->id, 'status' => 'active', 'trial_enforced' => false, 'trial_ends_at' => now()->subDay()]);
        $user = User::factory()->create(['clinic_id' => $clinic->id]);

        $this->assertFalse($clinic->fresh()->isReadOnly());
        $this->actingAs($user)->post('/services', [
            'name_ar' => 'حشو', 'category' => 'restorative', 'price' => 100, 'duration_min' => 30,
        ])->assertSessionMissing('error');
    }

    public function test_pricing_page_and_inquiry(): void
    {
        $this->get('/pricing')->assertOk();

        // Honeypot is silently accepted but stores nothing.
        $this->post('/pricing/inquiry', ['name' => 'Bot', 'website' => 'http://spam', 'email' => 'bot@x.com'])
            ->assertSessionHas('success');
        $this->assertSame(0, SalesInquiry::count());

        $this->post('/pricing/inquiry', ['name' => 'Dr A'])
            ->assertSessionHasErrors(['phone', 'email']);

        $this->post('/pricing/inquiry', ['name' => 'Dr A', 'phone' => '01000000000', 'clinic_name' => 'Smile'])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('sales_inquiries', ['name' => 'Dr A', 'clinic_name' => 'Smile', 'status' => 'new']);
    }

    public function test_platform_requires_staff_and_manages_plans_and_subscriptions(): void
    {
        $this->get('/platform')->assertRedirect(route('platform.login'));

        $admin = PlatformAdmin::create(['name' => 'Staff', 'email' => 'staff@example.com', 'password' => 'staff-secret-123']);

        $this->post('/platform/login', ['email' => 'staff@example.com', 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
        $this->post('/platform/login', ['email' => 'staff@example.com', 'password' => 'staff-secret-123'])
            ->assertRedirect(route('platform.dashboard'));

        $this->get('/platform')->assertOk();
        $this->get('/platform/plans')->assertOk();
        $this->get('/platform/inquiries')->assertOk();

        $this->post('/platform/plans', [
            'slug' => 'solo', 'name_ar' => 'منفرد', 'name_en' => 'Solo', 'is_public' => true, 'sort' => 1,
        ])->assertSessionHas('success');
        $plan = Plan::where('slug', 'solo')->firstOrFail();

        $this->get('/pricing')->assertOk();

        $clinic = Clinic::factory()->create();
        Subscription::create(['clinic_id' => $clinic->id, 'status' => 'trialing', 'trial_enforced' => true, 'trial_ends_at' => now()->subDay()]);
        $this->assertTrue($clinic->fresh()->isReadOnly());

        $this->put("/platform/clinics/{$clinic->id}/subscription", ['plan_id' => $plan->id, 'status' => 'active'])
            ->assertSessionHas('success');
        $this->assertFalse($clinic->fresh()->isReadOnly());
        $this->assertDatabaseHas('subscriptions', ['clinic_id' => $clinic->id, 'status' => 'active', 'plan_id' => $plan->id]);

        // Suspending logs the clinic's users out.
        $user = User::factory()->create(['clinic_id' => $clinic->id]);
        $this->patch("/platform/clinics/{$clinic->id}/suspend")->assertSessionHas('success');
        $this->actingAs($user)->get('/dashboard')->assertRedirect('/login');
        $this->patch("/platform/clinics/{$clinic->id}/activate")->assertSessionHas('success');

        // Tenant users can never reach the panel.
        auth('platform')->logout();
        $this->actingAs($user)->get('/platform')->assertRedirect(route('platform.login'));
    }

    public function test_platform_admin_is_not_a_tenant_user(): void
    {
        PlatformAdmin::create(['name' => 'Staff', 'email' => 'staff2@example.com', 'password' => 'staff-secret-123']);
        $this->post('/platform/login', ['email' => 'staff2@example.com', 'password' => 'staff-secret-123']);

        // The platform session carries no web guard: tenant areas treat staff as logged out.
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/patients')->assertRedirect('/login');
    }

    public function test_existing_clinic_backfilled_active_never_read_only(): void
    {
        // The migration backfills pre-existing clinics; simulate that shape directly.
        $clinic = Clinic::factory()->create();
        Subscription::create(['clinic_id' => $clinic->id, 'status' => 'active', 'trial_enforced' => false, 'note' => 'Existing clinic before the sales platform; activated by default.']);
        $this->assertFalse($clinic->fresh()->isReadOnly());
    }
}

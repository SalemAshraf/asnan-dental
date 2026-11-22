<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_command_creates_fixture_once_and_refuses_rerun(): void
    {
        $pwFile = tempnam(sys_get_temp_dir(), 'dempw');
        file_put_contents($pwFile, 'demo-test-only-12+');
        chmod($pwFile, 0600);
        $this->artisan('taqwim:demo', ['--password-file' => $pwFile])->assertSuccessful();
        unlink($pwFile);
        $this->assertDatabaseHas('users', ['email' => 'owner@asnan.test']);
        $clinic = Clinic::where('name', 'عيادة الابتسامة لطب الأسنان')->first();
        $this->assertNotNull($clinic);
        $this->assertTrue((bool) $clinic->ai_enabled);
        $patients = \App\Models\Patient::where('clinic_id', $clinic->id)->count();
        $this->assertGreaterThan(5, $patients);

        $this->artisan('taqwim:demo')->assertFailed();
        $this->assertEquals(1, User::where('email', 'owner@asnan.test')->count());
        $this->assertEquals($patients, \App\Models\Patient::where('clinic_id', $clinic->id)->count());
    }
}

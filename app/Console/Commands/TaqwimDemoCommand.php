<?php

namespace App\Console\Commands;

use App\Models\Clinic;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;

/**
 * Additive synthetic demo fixture. Creates the fictional demo clinic and its
 * demo logins once, and refuses to run again if any of those records already
 * exist. It never wipes or resets anything. The demo login password comes
 * from DEMO_PASSWORD (required outside local) and is never printed.
 */
class TaqwimDemoCommand extends Command
{
    protected $signature = 'taqwim:demo';

    protected $description = 'Create the synthetic demo clinic once (additive; refuses if demo records exist)';

    public const DEMO_EMAILS = ['owner@asnan.test', 'dentist@asnan.test', 'reception@asnan.test'];

    public function handle(): int
    {
        if (User::whereIn('email', self::DEMO_EMAILS)->exists()
            || Clinic::where('name', 'عيادة الابتسامة لطب الأسنان')->exists()) {
            $this->error('Demo records already exist. Nothing was changed; this command never resets data.');

            return self::FAILURE;
        }

        (new DatabaseSeeder)->run();

        Clinic::where('name', 'عيادة الابتسامة لطب الأسنان')->first()?->forceFill(['ai_enabled' => true])->save();

        $this->info('Synthetic demo clinic created (fictional data only).');
        $this->line('Demo owner login: owner@asnan.test with the DEMO_PASSWORD you set (never stored or printed here).');

        return self::SUCCESS;
    }
}

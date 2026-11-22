<?php

namespace App\Console\Commands;

use App\Models\Clinic;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;

/**
 * Additive synthetic demo fixture. Creates the fictional demo clinic and its
 * demo logins once, and refuses to run again if any of those records already
 * exist. It never wipes or resets anything.
 *
 * The demo password comes from --password-file (0600 file, min 12 chars),
 * the DEMO_PASSWORD environment variable, or a hidden interactive prompt.
 * It is never printed, logged or passed as a command-line argument.
 */
class TaqwimDemoCommand extends Command
{
    protected $signature = 'taqwim:demo {--password-file= : Path to a 0600 file containing the demo password (min 12 chars)}';

    protected $description = 'Create the synthetic demo clinic once (additive; refuses if demo records exist)';

    public const DEMO_EMAILS = ['owner@asnan.test', 'dentist@asnan.test', 'reception@asnan.test'];

    public function handle(): int
    {
        if (User::whereIn('email', self::DEMO_EMAILS)->exists()
            || Clinic::where('name', 'عيادة الابتسامة لطب الأسنان')->exists()) {
            $this->error('Demo records already exist. Nothing was changed; this command never resets data.');

            return self::FAILURE;
        }

        $password = $this->resolvePassword();
        if ($password === null) {
            return self::FAILURE;
        }

        if ($password !== '') {
            putenv('DEMO_PASSWORD='.$password);
            $_ENV['DEMO_PASSWORD'] = $password;
        }

        (new DatabaseSeeder)->run();

        Clinic::where('name', 'عيادة الابتسامة لطب الأسنان')->first()?->forceFill(['ai_enabled' => true])->save();

        $this->info('Synthetic demo clinic created (fictional data only).');
        $this->line('Demo owner login: owner@asnan.test with the password you provided (never stored or printed here).');

        return self::SUCCESS;
    }

    /**
     * Returns the password, '' when DEMO_PASSWORD env already covers it, or null on failure.
     */
    private function resolvePassword(): ?string
    {
        if ($file = $this->option('password-file')) {
            if (! is_file($file) || ! is_readable($file)) {
                $this->error("Password file not readable: {$file}");

                return null;
            }
            if ((fileperms($file) & 0777) !== 0600) {
                $this->error("Password file must have 0600 permissions: chmod 600 {$file}");

                return null;
            }
            $password = trim((string) file_get_contents($file));
            if (strlen($password) < 12) {
                $this->error('Demo password must be at least 12 characters.');

                return null;
            }

            return $password;
        }

        if (env('DEMO_PASSWORD')) {
            return ''; // seeder reads it from the environment
        }

        if (! $this->input->isInteractive()) {
            $this->error('Provide --password-file or set DEMO_PASSWORD; non-interactive runs never prompt.');

            return null;
        }

        $password = (string) $this->secret('Set the demo login password (min 12 chars; store it in a password manager)');
        $confirm = (string) $this->secret('Confirm the demo login password');
        if ($password === '' || $password !== $confirm || strlen($password) < 12) {
            $this->error('Passwords did not match or were shorter than 12 characters. Nothing was created.');

            return null;
        }

        return $password;
    }
}

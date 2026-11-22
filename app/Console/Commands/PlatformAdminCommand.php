<?php

namespace App\Console\Commands;

use App\Models\PlatformAdmin;
use Illuminate\Console\Command;

/** Platform staff are created only here, on the server console, with a hidden password prompt. Never from the web. */
class PlatformAdminCommand extends Command
{
    protected $signature = 'platform:admin {email : Login email} {--name= : Display name} {--revoke : Delete this platform admin instead}';

    protected $description = 'Create (or revoke) a platform staff login for the sales/admin panel';

    public function handle(): int
    {
        $email = strtolower($this->argument('email'));
        $existing = PlatformAdmin::where('email', $email)->first();
        if ($this->option('revoke')) {
            if (! $existing) {
                $this->error('No such platform admin.');

                return self::FAILURE;
            }
            $existing->delete();
            $this->info('Platform admin removed.');

            return self::SUCCESS;
        }
        if ($existing) {
            $this->error('A platform admin with this email already exists. Nothing was changed.');

            return self::FAILURE;
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email.');

            return self::FAILURE;
        }
        if (! $this->input->isInteractive()) {
            $this->error('Needs an interactive terminal for the hidden password prompt. Nothing was changed.');

            return self::FAILURE;
        }
        $pw = (string) $this->secret('Password (hidden, at least 12 characters)');
        $again = (string) $this->secret('Repeat the password');
        if (strlen($pw) < 12 || $pw !== $again) {
            $this->error('Too short or the two entries differ. Nothing was changed.');

            return self::FAILURE;
        }
        PlatformAdmin::create(['name' => $this->option('name') ?: 'Platform admin', 'email' => $email, 'password' => $pw]);
        $this->info("Platform admin created: $email (login at /platform/login). The password is not displayed or stored in plain text.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/** Append-only record of platform staff actions. Never stores patient data, phones or message text. */
class PlatformLog
{
    public static function record(string $action, ?int $clinicId = null, array $meta = []): void
    {
        DB::table('platform_activity')->insert([
            'platform_admin_id' => auth('platform')->id(), 'action' => $action, 'clinic_id' => $clinicId,
            'meta' => $meta ? json_encode($meta) : null, 'ip' => request()?->ip(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}

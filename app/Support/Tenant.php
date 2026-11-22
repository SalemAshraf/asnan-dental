<?php

namespace App\Support;

/**
 * Holds the current clinic (tenant) for the request lifecycle.
 * Set by EnsureClinicContext middleware from the authenticated user.
 * Null in console/seeding unless explicitly set.
 */
class Tenant
{
    private static ?int $clinicId = null;

    public static function set(?int $clinicId): void
    {
        self::$clinicId = $clinicId;
    }

    public static function id(): ?int
    {
        return self::$clinicId;
    }
}

<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    /**
     * Reject overlaps for the same dentist or the same chair in a branch,
     * among active appointments only.
     */
    public function assertNoOverlap(
        int $branchId,
        int $dentistId,
        ?string $chair,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        ?int $ignoreId = null
    ): void {
        $base = Appointment::where('branch_id', $branchId)
            ->whereIn('status', Appointment::ACTIVE_STATUSES)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId));

        $dentistClash = (clone $base)->where('dentist_id', $dentistId)->exists();
        if ($dentistClash) {
            throw ValidationException::withMessages([
                'starts_at' => ['يوجد حجز آخر لنفس الطبيب في هذا الوقت.'],
            ]);
        }

        if ($chair !== null && $chair !== '') {
            $chairClash = (clone $base)->where('chair', $chair)->exists();
            if ($chairClash) {
                throw ValidationException::withMessages([
                    'chair' => ['هذا الكرسي محجوز في هذا الوقت.'],
                ]);
            }
        }
    }
}

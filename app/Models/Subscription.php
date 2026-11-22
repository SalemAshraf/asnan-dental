<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    public const STATUSES = ['pending', 'trialing', 'active', 'past_due', 'cancelled'];

    protected $guarded = [];

    protected $casts = ['trial_ends_at' => 'datetime', 'current_period_end' => 'datetime', 'trial_enforced' => 'boolean'];

    /** A trial from the new-clinic sign-up (trial_enforced) whose end date passed without activation. Older rows never match. Computed from the date, no scheduler involved. */
    public function trialExpired(): bool
    {
        return $this->trial_enforced && $this->status === 'trialing' && $this->trial_ends_at !== null && $this->trial_ends_at->isPast();
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}

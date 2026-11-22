<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Support\Tenant;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToClinic
{
    public static function bootBelongsToClinic(): void
    {
        static::addGlobalScope('clinic', function (Builder $query) {
            // Route-model binding runs before the auth middleware, so fall
            // back to the authenticated user's clinic when the request
            // context is not set yet. The User model itself must never
            // trigger auth resolution here: the session guard loads the
            // User through this very query, which would recurse.
            $clinicId = Tenant::id();
            if ($clinicId === null) {
                if ($query->getModel() instanceof User) {
                    $guard = auth()->guard();
                    $clinicId = $guard->hasUser() ? $guard->user()?->clinic_id : null;
                } else {
                    $clinicId = auth()->user()?->clinic_id;
                }
            }
            if ($clinicId !== null) {
                $query->where($query->getModel()->getTable().'.clinic_id', $clinicId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->clinic_id) && Tenant::id() !== null) {
                $model->clinic_id = Tenant::id();
            }
        });
    }
}

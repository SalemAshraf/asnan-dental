<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use App\Support\Tenant;

trait Auditable
{
    /** Fields never written to the audit trail (sensitive). */
    protected function auditExcludedFields(): array
    {
        return ['password', 'remember_token', 'national_id'];
    }

    public static function bootAuditable(): void
    {
        $write = function (string $action, $model) {
            $changes = null;
            if ($action === 'updated') {
                $dirty = $model->getDirty();
                $old = array_intersect_key($model->getOriginal(), $dirty);
                foreach ($model->auditExcludedFields() as $f) {
                    unset($dirty[$f], $old[$f]);
                }
                if ($dirty === []) {
                    return;
                }
                $changes = ['old' => $old, 'new' => $dirty];
            }

            AuditLog::create([
                'clinic_id' => $model->clinic_id ?? Tenant::id(),
                'user_id' => auth()->id(),
                'action' => $action,
                'auditable_type' => $model->getMorphClass(),
                'auditable_id' => $model->getKey(),
                'changes' => $changes,
                'ip' => request()?->ip(),
                'user_agent' => substr((string) request()?->userAgent(), 0, 255),
            ]);
        };

        static::created(fn ($m) => $write('created', $m));
        static::updated(fn ($m) => $write('updated', $m));
        static::deleted(fn ($m) => $write('deleted', $m));
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'clinic_id', 'user_id', 'action', 'auditable_type', 'auditable_id',
        'changes', 'ip', 'user_agent',
    ];

    protected $casts = ['changes' => 'array', 'created_at' => 'datetime'];
}

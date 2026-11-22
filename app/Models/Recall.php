<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;

class Recall extends Model
{
    use Auditable, BelongsToClinic;

    public const STATUSES = ['pending', 'done', 'cancelled'];

    protected $fillable = [
        'clinic_id', 'patient_id', 'service_id', 'due_date',
        'status', 'reason', 'notes', 'created_by', 'done_at',
    ];

    protected $casts = ['due_date' => 'date', 'done_at' => 'datetime'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}

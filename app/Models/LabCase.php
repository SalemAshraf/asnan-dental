<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabCase extends Model
{
    use Auditable, BelongsToClinic, HasFactory;

    public const WORK_TYPES = ['crown', 'bridge', 'denture', 'veneer', 'implant', 'orthodontic', 'other'];

    public const STATUSES = ['sent', 'in_progress', 'received', 'fitted', 'cancelled'];

    public const OPEN_STATUSES = ['sent', 'in_progress', 'received'];

    protected $fillable = [
        'clinic_id', 'patient_id', 'dentist_id', 'lab_name', 'work_type', 'shade',
        'sent_on', 'due_on', 'status', 'cost_minor', 'notes',
    ];

    protected $casts = ['sent_on' => 'date', 'due_on' => 'date'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function dentist()
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }
}

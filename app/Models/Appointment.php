<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use Auditable, BelongsToClinic, SoftDeletes, HasFactory;

    public const STATUSES = ['scheduled', 'confirmed', 'checked_in', 'completed', 'cancelled', 'no_show'];
    public const ACTIVE_STATUSES = ['scheduled', 'confirmed', 'checked_in'];

    protected $fillable = [
        'clinic_id', 'branch_id', 'patient_id', 'dentist_id', 'chair',
        'starts_at', 'ends_at', 'status', 'reason', 'notes', 'booked_via',
    ];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function dentist()
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReminderMessage extends Model
{
    use BelongsToClinic, HasFactory;

    public const KINDS = ['recall', 'appointment'];

    public const STATUSES = ['queued', 'sent', 'skipped'];

    protected $fillable = [
        'clinic_id', 'patient_id', 'recall_id', 'appointment_id', 'kind',
        'channel', 'scheduled_for', 'status', 'dedupe_key', 'sent_at', 'sent_by',
    ];

    protected $casts = [
        'scheduled_for' => 'date',
        'sent_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function recall()
    {
        return $this->belongsTo(Recall::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}

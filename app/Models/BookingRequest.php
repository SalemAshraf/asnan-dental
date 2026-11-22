<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingRequest extends Model
{
    use BelongsToClinic;

    public const STATUS_NEW = 'new';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_DECLINED = 'declined';

    public const STATUSES = [self::STATUS_NEW, self::STATUS_CONFIRMED, self::STATUS_DECLINED];

    protected $fillable = [
        'clinic_id', 'name', 'phone', 'service_id', 'preferred_date', 'preferred_time',
        'notes', 'status', 'patient_id', 'appointment_id',
    ];

    protected $casts = ['preferred_date' => 'date'];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}

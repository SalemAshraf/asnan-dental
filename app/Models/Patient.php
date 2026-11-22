<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use Auditable, BelongsToClinic, SoftDeletes, HasFactory;

    protected $fillable = [
        'clinic_id', 'branch_id', 'file_no', 'full_name', 'phone', 'whatsapp',
        'birth_date', 'gender', 'national_id', 'medical_alerts', 'notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'medical_alerts' => 'array',
        'national_id' => 'encrypted',
    ];

    public function consents(): HasMany
    {
        return $this->hasMany(ConsentRecord::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function odontogramEntries(): HasMany
    {
        return $this->hasMany(OdontogramEntry::class);
    }

    public function treatmentPlans(): HasMany
    {
        return $this->hasMany(TreatmentPlan::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function hasConsent(string $type): bool
    {
        return $this->consents()->where('type', $type)->exists();
    }
}

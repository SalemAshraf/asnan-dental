<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use Auditable, BelongsToClinic, HasFactory;

    protected $fillable = ['clinic_id', 'patient_id', 'user_id', 'prescribed_on', 'notes'];

    protected $casts = ['prescribed_on' => 'date'];

    public function items()
    {
        return $this->hasMany(PrescriptionItem::class)->orderBy('sort');
    }

    public function prescriber()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}

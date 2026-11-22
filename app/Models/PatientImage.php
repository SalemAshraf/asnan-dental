<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientImage extends Model
{
    use Auditable, BelongsToClinic, HasFactory;

    public const KINDS = ['xray', 'photo'];

    protected $fillable = [
        'clinic_id', 'patient_id', 'user_id', 'kind', 'title', 'path',
        'original_name', 'mime', 'size', 'taken_on', 'notes',
    ];

    protected $casts = [
        'taken_on' => 'date',
        'size' => 'integer',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

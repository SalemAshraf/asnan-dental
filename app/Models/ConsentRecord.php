<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;

class ConsentRecord extends Model
{
    use BelongsToClinic, HasFactory;

    public const TYPES = ['data_processing', 'treatment', 'marketing'];
    public const METHODS = ['verbal_logged', 'written', 'digital'];

    protected $fillable = [
        'clinic_id', 'patient_id', 'type', 'text_snapshot',
        'method', 'given_at', 'recorded_by',
    ];

    protected $casts = ['given_at' => 'datetime'];
}

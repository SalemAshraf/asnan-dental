<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use BelongsToClinic, HasFactory;

    public const CATEGORIES = [
        'exam', 'preventive', 'restorative', 'endo', 'perio',
        'surgery', 'prostho', 'ortho', 'cosmetic', 'other',
    ];

    protected $fillable = [
        'clinic_id', 'code', 'name_ar', 'name_en', 'category',
        'default_price_minor', 'duration_min', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];
}

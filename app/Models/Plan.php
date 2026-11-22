<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $guarded = [];

    protected $casts = [
        'features_ar' => 'array',
        'features_en' => 'array',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'signup_default' => 'boolean',
        'price' => 'decimal:2',
        'price_yearly' => 'decimal:2',
    ];
}

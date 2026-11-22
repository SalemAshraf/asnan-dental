<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use BelongsToClinic, HasFactory;

    protected $fillable = ['clinic_id', 'name', 'address', 'phone', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}

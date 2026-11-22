<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;

class InsuranceCompany extends Model
{
    use BelongsToClinic;

    protected $fillable = ['clinic_id', 'name', 'phone', 'email', 'notes'];

    public function claims()
    {
        return $this->hasMany(InsuranceClaim::class);
    }
}

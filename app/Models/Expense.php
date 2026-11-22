<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use Auditable, BelongsToClinic, HasFactory;

    public const CATEGORIES = ['rent', 'salaries', 'lab', 'supplies', 'utilities', 'marketing', 'other'];

    protected $fillable = [
        'clinic_id', 'category', 'amount_minor', 'spent_on', 'vendor', 'notes', 'created_by',
    ];

    protected $casts = ['spent_on' => 'date'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreatmentPlanItem extends Model
{
    use HasFactory;
    public const STATUSES = ['proposed', 'accepted', 'in_progress', 'completed', 'rejected'];

    protected $fillable = [
        'plan_id', 'service_id', 'tooth_number', 'surfaces', 'description',
        'price_minor', 'discount_minor', 'qty', 'status', 'sort',
    ];

    public function plan()
    {
        return $this->belongsTo(TreatmentPlan::class, 'plan_id');
    }

    public function lineTotal(): int
    {
        return ($this->price_minor - $this->discount_minor) * $this->qty;
    }
}

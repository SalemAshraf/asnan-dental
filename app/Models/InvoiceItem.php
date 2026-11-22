<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'invoice_id', 'treatment_plan_item_id', 'description', 'tooth_number',
        'qty', 'unit_price_minor', 'discount_minor', 'total_minor',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function treatmentPlanItem()
    {
        return $this->belongsTo(TreatmentPlanItem::class, 'treatment_plan_item_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use Auditable, BelongsToClinic, HasFactory;

    public const METHODS = ['cash', 'card', 'instapay', 'bank_transfer', 'wallet'];

    protected $fillable = [
        'clinic_id', 'invoice_id', 'patient_id', 'amount_minor',
        'method', 'reference', 'paid_at', 'received_by', 'notes',
    ];

    protected $casts = ['paid_at' => 'datetime'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}

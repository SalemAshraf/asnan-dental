<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use Auditable, BelongsToClinic, HasFactory;

    public const STATUSES = ['draft', 'issued', 'partially_paid', 'paid', 'void'];

    protected $fillable = [
        'clinic_id', 'branch_id', 'patient_id', 'number', 'status', 'currency',
        'subtotal_minor', 'discount_minor', 'tax_rate_bp', 'tax_minor',
        'total_minor', 'paid_minor', 'issued_at', 'due_at', 'notes',
    ];

    protected $casts = ['issued_at' => 'datetime', 'due_at' => 'datetime'];

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function balanceMinor(): int
    {
        return $this->total_minor - $this->paid_minor;
    }
}

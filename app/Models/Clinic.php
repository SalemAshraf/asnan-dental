<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Clinic extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'country', 'currency', 'timezone', 'tax_rate_bp',
        'invoice_prefix', 'phone', 'address',
    ];

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Allocate the next invoice number atomically (row lock). */
    public function allocateInvoiceNumber(): string
    {
        return DB::transaction(function () {
            $clinic = static::lockForUpdate()->findOrFail($this->id);
            $seq = $clinic->next_invoice_seq;
            $clinic->increment('next_invoice_seq');

            return sprintf('%s-%06d', $clinic->invoice_prefix, $seq);
        });
    }

    /** Allocate the next patient file number atomically (row lock). */
    public function allocatePatientFileNo(): int
    {
        return DB::transaction(function () {
            $clinic = static::lockForUpdate()->findOrFail($this->id);
            $seq = $clinic->next_patient_seq;
            $clinic->increment('next_patient_seq');

            return (int) $seq;
        });
    }
}

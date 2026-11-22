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

    protected $casts = ['suspended_at' => 'datetime', 'setup_dismissed_at' => 'datetime'];

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscription(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    /** Read-only: view existing data, add or change nothing. Triggered by an expired, unactivated trial or by a subscription still pending activation; clinics without a subscription never do. */
    public function isReadOnly(): bool
    {
        return (bool) $this->subscription?->trialExpired() || $this->subscription?->status === 'pending';
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

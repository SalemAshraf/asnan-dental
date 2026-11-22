<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;

class InsuranceClaim extends Model
{
    use Auditable, BelongsToClinic;

    public const STATUSES = ['submitted', 'approved', 'rejected', 'paid'];

    public const OPEN_STATUSES = ['submitted', 'approved'];

    protected $fillable = [
        'clinic_id', 'patient_id', 'insurance_company_id', 'invoice_id',
        'claim_no', 'amount', 'status', 'submitted_on', 'decided_on', 'notes',
    ];

    protected $casts = ['submitted_on' => 'date', 'decided_on' => 'date'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function company()
    {
        return $this->belongsTo(InsuranceCompany::class, 'insurance_company_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}

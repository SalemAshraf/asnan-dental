<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class TreatmentPlan extends Model
{
    use Auditable, BelongsToClinic, HasFactory;

    public const STATUSES = ['draft', 'proposed', 'accepted', 'in_progress', 'completed', 'cancelled'];

    public const TRANSITIONS = [
        'draft' => ['proposed', 'cancelled'],
        'proposed' => ['accepted', 'cancelled', 'draft'],
        'accepted' => ['in_progress', 'cancelled'],
        'in_progress' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    protected $fillable = [
        'clinic_id', 'patient_id', 'dentist_id', 'title', 'status', 'total_minor', 'notes',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(TreatmentPlanItem::class, 'plan_id')->orderBy('sort');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function transitionTo(string $status): void
    {
        if (! in_array($status, self::TRANSITIONS[$this->status] ?? [], true)) {
            throw new InvalidArgumentException("Cannot move plan from {$this->status} to {$status}");
        }
        $this->status = $status;
        $this->save();
    }

    public function recalcTotal(): void
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += ($item->price_minor - $item->discount_minor) * $item->qty;
        }
        $this->total_minor = $total;
        $this->save();
    }
}

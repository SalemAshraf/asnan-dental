<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use Auditable, BelongsToClinic, HasFactory;

    public const UNITS = ['piece', 'box', 'pack', 'bottle', 'kit'];

    protected $fillable = ['clinic_id', 'name', 'unit', 'quantity', 'low_stock_threshold', 'notes'];

    protected $casts = ['quantity' => 'decimal:3', 'low_stock_threshold' => 'decimal:3'];

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class)->latest('id');
    }

    public function isLow(): bool
    {
        return $this->low_stock_threshold !== null && $this->quantity <= $this->low_stock_threshold;
    }
}

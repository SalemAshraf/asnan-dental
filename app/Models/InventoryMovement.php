<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use BelongsToClinic;

    public const REASONS = ['purchase', 'usage', 'adjustment', 'procedure'];

    public const UPDATED_AT = null;

    protected $fillable = ['clinic_id', 'inventory_item_id', 'change', 'reason', 'reference', 'user_id', 'note', 'created_at'];

    protected $casts = ['change' => 'decimal:3', 'created_at' => 'datetime'];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

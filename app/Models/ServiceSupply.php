<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;

class ServiceSupply extends Model
{
    use BelongsToClinic;

    protected $fillable = ['clinic_id', 'service_id', 'inventory_item_id', 'qty'];

    protected $casts = ['qty' => 'decimal:3'];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}

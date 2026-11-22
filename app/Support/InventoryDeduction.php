<?php

namespace App\Support;

use App\Models\InventoryMovement;
use App\Models\ServiceSupply;
use App\Models\TreatmentPlanItem;

class InventoryDeduction
{
    /**
     * Deduct mapped supplies when a plan item is completed. Idempotent per
     * plan item: a procedure movement with the same reference is never
     * recorded twice.
     */
    public static function deductForPlanItem(TreatmentPlanItem $item, ?int $userId = null): void
    {
        if (! $item->service_id) {
            return;
        }

        $reference = 'plan_item:'.$item->id;
        if (InventoryMovement::where('reference', $reference)->exists()) {
            return;
        }

        $rules = ServiceSupply::with('item')->where('service_id', $item->service_id)->get();
        foreach ($rules as $rule) {
            if (! $rule->item) {
                continue;
            }
            $change = -1 * $rule->qty * max(1, (int) $item->qty);
            $rule->item->increment('quantity', $change);
            InventoryMovement::create([
                'clinic_id' => $rule->clinic_id,
                'inventory_item_id' => $rule->inventory_item_id,
                'change' => $change,
                'reason' => 'procedure',
                'reference' => $reference,
                'user_id' => $userId,
                'created_at' => now(),
            ]);
        }
    }
}

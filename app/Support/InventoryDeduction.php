<?php

namespace App\Support;

use App\Models\InventoryMovement;
use App\Models\ServiceSupply;
use App\Models\TreatmentPlanItem;
use Illuminate\Support\Facades\DB;

class InventoryDeduction
{
    /**
     * Deduct mapped supplies when a plan item is completed. Serialized with a
     * row lock on the plan item so concurrent completions cannot double-deduct.
     * A reversal restores stock if the item later leaves "completed"; completing
     * again after a reversal deducts again. Each pair of movements carries the
     * plan-item reference for auditability.
     */
    public static function deductForPlanItem(TreatmentPlanItem $item, ?int $userId = null): void
    {
        if (! $item->service_id) {
            return;
        }

        DB::transaction(function () use ($item, $userId) {
            $locked = TreatmentPlanItem::whereKey($item->id)->lockForUpdate()->first();
            if (! $locked || $locked->status !== 'completed') {
                return;
            }

            $reference = 'plan_item:'.$locked->id;
            $movements = InventoryMovement::where('reference', $reference)
                ->where('reason', 'procedure')->lockForUpdate()->get();
            $deducted = $movements->where('change', '<', 0)->count();
            $reversed = $movements->where('change', '>', 0)->count();
            if ($deducted > $reversed) {
                return; // already deducted and not reversed
            }

            $rules = ServiceSupply::with('item')->where('service_id', $locked->service_id)->get();
            foreach ($rules as $rule) {
                if (! $rule->item) {
                    continue;
                }
                $change = -1 * $rule->qty * max(1, (int) $locked->qty);
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
        });
    }

    /**
     * Restore stock when a previously completed plan item is un-completed
     * (cancellation/correction). One compensating positive movement per prior
     * deduction; safe to call repeatedly.
     */
    public static function reverseForPlanItem(TreatmentPlanItem $item, ?int $userId = null): void
    {
        if (! $item->service_id) {
            return;
        }

        DB::transaction(function () use ($item, $userId) {
            $locked = TreatmentPlanItem::whereKey($item->id)->lockForUpdate()->first();
            if (! $locked || $locked->status === 'completed') {
                return;
            }

            $reference = 'plan_item:'.$locked->id;
            $movements = InventoryMovement::where('reference', $reference)
                ->where('reason', 'procedure')->lockForUpdate()->get();
            $deducted = $movements->where('change', '<', 0)->count();
            $reversed = $movements->where('change', '>', 0)->count();
            if ($deducted === 0 || $deducted <= $reversed) {
                return; // nothing to restore
            }

            $rules = ServiceSupply::with('item')->where('service_id', $locked->service_id)->get();
            foreach ($rules as $rule) {
                if (! $rule->item) {
                    continue;
                }
                $change = $rule->qty * max(1, (int) $locked->qty);
                $rule->item->increment('quantity', $change);
                InventoryMovement::create([
                    'clinic_id' => $rule->clinic_id,
                    'inventory_item_id' => $rule->inventory_item_id,
                    'change' => $change,
                    'reason' => 'procedure',
                    'reference' => $reference,
                    'user_id' => $userId,
                    'note' => 'reversal',
                    'created_at' => now(),
                ]);
            }
        });
    }
}

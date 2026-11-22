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

            $rules = ServiceSupply::with('item')->where('service_id', $locked->service_id)->get()
                ->filter(fn ($rule) => $rule->item !== null);

            // Lock the stock rows in a stable order before reading balances so
            // concurrent completions of different plan items consuming the same
            // stock serialize instead of racing.
            $stockRows = \App\Models\InventoryItem::whereIn('id', $rules->pluck('inventory_item_id'))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            // All-or-nothing: if any mapped item lacks stock, deduct nothing and
            // roll back rather than leaving a half-applied deduction. Completing
            // the treatment is never blocked by stock tracking.
            $need = $rules->mapWithKeys(fn ($rule) => [
                $rule->inventory_item_id => $rule->qty * max(1, (int) $locked->qty),
            ]);
            foreach ($need as $itemId => $qty) {
                $item = $stockRows->get($itemId);
                if (! $item || $item->quantity < $qty) {
                    return;
                }
            }

            foreach ($rules as $rule) {
                $change = -1 * $need[$rule->inventory_item_id];
                $stockRows[$rule->inventory_item_id]->increment('quantity', $change);
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

            // Reverse exactly what was deducted (recorded movements), so later
            // rule changes cannot distort the compensating amounts.
            foreach ($movements->where('change', '<', 0) as $deduction) {
                $change = -1 * (float) $deduction->change;
                \App\Models\InventoryItem::whereKey($deduction->inventory_item_id)
                    ->lockForUpdate()->first()?->increment('quantity', $change);
                InventoryMovement::create([
                    'clinic_id' => $deduction->clinic_id,
                    'inventory_item_id' => $deduction->inventory_item_id,
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

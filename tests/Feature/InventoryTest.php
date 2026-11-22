<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Patient;
use App\Models\Service;
use App\Models\ServiceSupply;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_movements_and_rules(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/inventory/items', [
            'name' => 'حشوة كومبوزيت', 'unit' => 'pack', 'quantity' => 10, 'low_stock_threshold' => 3,
        ])->assertRedirect();
        $item = InventoryItem::first();
        $this->assertSame('10.000', (string) $item->quantity);

        $this->actingAs($user)->post('/inventory/movements', [
            'inventory_item_id' => $item->id, 'reason' => 'purchase', 'qty' => 5,
        ])->assertRedirect();
        $this->assertSame('15.000', (string) $item->fresh()->quantity);

        $this->actingAs($user)->post('/inventory/movements', [
            'inventory_item_id' => $item->id, 'reason' => 'usage', 'qty' => 2,
        ])->assertRedirect();
        $this->assertSame('13.000', (string) $item->fresh()->quantity);

        $service = Service::factory()->create(['clinic_id' => $user->clinic_id]);
        $this->actingAs($user)->post('/inventory/rules', [
            'service_id' => $service->id, 'inventory_item_id' => $item->id, 'qty' => 0.5,
        ])->assertRedirect();
        $this->assertDatabaseHas('service_supplies', [
            'service_id' => $service->id, 'inventory_item_id' => $item->id, 'qty' => 0.5,
        ]);
    }

    public function test_completing_plan_item_deducts_supplies_once(): void
    {
        $user = User::factory()->create();
        $clinicId = $user->clinic_id;
        $patient = Patient::factory()->create(['clinic_id' => $clinicId]);
        $service = Service::factory()->create(['clinic_id' => $clinicId]);
        $item = InventoryItem::create(['clinic_id' => $clinicId, 'name' => 'حشوة', 'unit' => 'pack', 'quantity' => 10]);
        ServiceSupply::create(['clinic_id' => $clinicId, 'service_id' => $service->id, 'inventory_item_id' => $item->id, 'qty' => 1.5]);

        $plan = TreatmentPlan::factory()->create(['clinic_id' => $clinicId, 'patient_id' => $patient->id, 'dentist_id' => $user->id]);
        $planItem = TreatmentPlanItem::create([
            'plan_id' => $plan->id, 'service_id' => $service->id, 'description' => 'حشو',
            'price_minor' => 10000, 'discount_minor' => 0, 'qty' => 2, 'status' => 'accepted',
        ]);

        $this->actingAs($user)->patch("/plan-items/{$planItem->id}", ['status' => 'completed'])->assertRedirect();
        $this->assertSame('7.000', (string) $item->fresh()->quantity); // 10 - 1.5*2
        $this->assertSame(1, InventoryMovement::where('reason', 'procedure')->count());

        // Un-completing reverses the deduction exactly once.
        $this->actingAs($user)->patch("/plan-items/{$planItem->id}", ['status' => 'in_progress'])->assertRedirect();
        $this->assertSame('10.000', (string) $item->fresh()->quantity);
        $this->assertSame(2, InventoryMovement::where('reason', 'procedure')->count());

        // Staying un-completed does not reverse twice.
        $this->actingAs($user)->patch("/plan-items/{$planItem->id}", ['status' => 'accepted'])->assertRedirect();
        $this->assertSame('10.000', (string) $item->fresh()->quantity);

        // Completing again after the reversal deducts again.
        $this->actingAs($user)->patch("/plan-items/{$planItem->id}", ['status' => 'completed'])->assertRedirect();
        $this->assertSame('7.000', (string) $item->fresh()->quantity);
        $this->assertSame(3, InventoryMovement::where('reason', 'procedure')->count());
    }

    public function test_insufficient_stock_rolls_back_whole_deduction_without_blocking_care(): void
    {
        $user = User::factory()->create();
        $clinicId = $user->clinic_id;
        $patient = Patient::factory()->create(['clinic_id' => $clinicId]);
        $service = Service::factory()->create(['clinic_id' => $clinicId]);
        $a = InventoryItem::create(['clinic_id' => $clinicId, 'name' => 'أ', 'unit' => 'piece', 'quantity' => 10]);
        $b = InventoryItem::create(['clinic_id' => $clinicId, 'name' => 'ب', 'unit' => 'piece', 'quantity' => 1]); // short
        ServiceSupply::create(['clinic_id' => $clinicId, 'service_id' => $service->id, 'inventory_item_id' => $a->id, 'qty' => 2]);
        ServiceSupply::create(['clinic_id' => $clinicId, 'service_id' => $service->id, 'inventory_item_id' => $b->id, 'qty' => 2]);

        $plan = TreatmentPlan::factory()->create(['clinic_id' => $clinicId, 'patient_id' => $patient->id, 'dentist_id' => $user->id]);
        $planItem = TreatmentPlanItem::create([
            'plan_id' => $plan->id, 'service_id' => $service->id, 'description' => 'حشو',
            'price_minor' => 10000, 'discount_minor' => 0, 'qty' => 1, 'status' => 'accepted',
        ]);

        $this->actingAs($user)->patch("/plan-items/{$planItem->id}", ['status' => 'completed'])->assertRedirect();

        $this->assertSame('completed', $planItem->fresh()->status); // care not blocked
        $this->assertSame('10.000', (string) $a->fresh()->quantity); // nothing deducted
        $this->assertSame('1.000', (string) $b->fresh()->quantity);
        $this->assertSame(0, InventoryMovement::count());
    }

    public function test_two_plan_items_consuming_same_stock_deduct_sequentially(): void
    {
        $user = User::factory()->create();
        $clinicId = $user->clinic_id;
        $patient = Patient::factory()->create(['clinic_id' => $clinicId]);
        $service = Service::factory()->create(['clinic_id' => $clinicId]);
        $item = InventoryItem::create(['clinic_id' => $clinicId, 'name' => 'حشوة', 'unit' => 'pack', 'quantity' => 5]);
        ServiceSupply::create(['clinic_id' => $clinicId, 'service_id' => $service->id, 'inventory_item_id' => $item->id, 'qty' => 3]);

        $plan = TreatmentPlan::factory()->create(['clinic_id' => $clinicId, 'patient_id' => $patient->id, 'dentist_id' => $user->id]);
        $mk = fn () => TreatmentPlanItem::create([
            'plan_id' => $plan->id, 'service_id' => $service->id, 'description' => 'حشو',
            'price_minor' => 10000, 'discount_minor' => 0, 'qty' => 1, 'status' => 'accepted',
        ]);
        $first = $mk();
        $second = $mk();

        $this->actingAs($user)->patch("/plan-items/{$first->id}", ['status' => 'completed'])->assertRedirect();
        $this->assertSame('2.000', (string) $item->fresh()->quantity);

        // Second completion sees the true remaining balance (2 < 3) and rolls back.
        $this->actingAs($user)->patch("/plan-items/{$second->id}", ['status' => 'completed'])->assertRedirect();
        $this->assertSame('2.000', (string) $item->fresh()->quantity);
        $this->assertSame(1, InventoryMovement::count());
        $this->assertSame('completed', $second->fresh()->status);
    }

    public function test_inventory_is_tenant_scoped(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreign = ServiceSupply::create([
            'clinic_id' => $other->clinic_id,
            'service_id' => Service::factory()->create(['clinic_id' => $other->clinic_id])->id,
            'inventory_item_id' => InventoryItem::create(['clinic_id' => $other->clinic_id, 'name' => 'x', 'unit' => 'piece', 'quantity' => 1])->id,
            'qty' => 1,
        ]);

        $this->actingAs($user)->delete("/inventory/rules/{$foreign->id}")->assertNotFound();
        $this->assertDatabaseHas('service_supplies', ['id' => $foreign->id]);
    }
}

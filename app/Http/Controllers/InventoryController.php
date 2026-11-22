<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Service;
use App\Models\ServiceSupply;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Inventory/Index', [
            'items' => InventoryItem::orderBy('name')->get()->map(fn ($i) => [
                ...$i->toArray(),
                'low' => $i->isLow(),
            ]),
            'movements' => InventoryMovement::with(['item:id,name,unit', 'user:id,name'])
                ->latest('id')->limit(40)->get(),
            'rules' => ServiceSupply::with(['service:id,name_ar,name_en', 'item:id,name,unit'])
                ->orderBy('id')->get(),
            'services' => Service::orderBy('name_ar')->get(['id', 'name_ar', 'name_en']),
        ]);
    }

    public function storeItem(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'unit' => ['required', Rule::in(InventoryItem::UNITS)],
            'quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        InventoryItem::create([
            'name' => $data['name'],
            'unit' => $data['unit'],
            'quantity' => $data['quantity'] ?? 0,
            'low_stock_threshold' => $data['low_stock_threshold'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return back();
    }

    public function storeMovement(Request $request)
    {
        $data = $request->validate([
            'inventory_item_id' => ['required', 'integer', 'exists:inventory_items,id'],
            'reason' => ['required', Rule::in(['purchase', 'usage', 'adjustment'])],
            'qty' => ['required', 'numeric', 'min:0.001', 'max:999999999'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $item = InventoryItem::findOrFail($data['inventory_item_id']);
        $change = $data['reason'] === 'purchase' ? abs($data['qty']) : -abs($data['qty']);

        $item->increment('quantity', $change);
        InventoryMovement::create([
            'inventory_item_id' => $item->id,
            'change' => $change,
            'reason' => $data['reason'],
            'user_id' => $request->user()->id,
            'note' => $data['note'] ?? null,
            'created_at' => now(),
        ]);

        return back();
    }

    public function storeRule(Request $request)
    {
        $data = $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'inventory_item_id' => ['required', 'integer', 'exists:inventory_items,id'],
            'qty' => ['required', 'numeric', 'min:0.001', 'max:9999'],
        ]);

        ServiceSupply::updateOrCreate(
            ['service_id' => $data['service_id'], 'inventory_item_id' => $data['inventory_item_id']],
            ['qty' => $data['qty']]
        );

        return back();
    }

    public function destroyRule(Request $request, ServiceSupply $serviceSupply)
    {
        $serviceSupply->delete();

        return back();
    }
}

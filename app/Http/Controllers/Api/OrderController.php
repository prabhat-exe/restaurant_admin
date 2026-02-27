<?php

namespace App\Http\Controllers\Api;

use App\Models\MenuItem;
use App\Models\ItemAddon;
use App\Models\ItemVariation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Place an order (store order and item details).
     */
    public function place(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'store_id' => 'required|integer',
            'store_name' => 'required|string',
            'order_category' => 'required|integer',
            'order_type' => 'required|integer',
            'total_quantity' => 'required|integer',
            'total_price' => 'required|numeric',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|integer',
            'items.*.item_name' => 'required|string',
            'items.*.price' => 'required|numeric',
            'items.*.total_price' => 'required|numeric',
            'items.*.quantity' => 'required|integer',
            'items.*.selected_variation.variation_id' => 'nullable|integer',
            'items.*.addons' => 'nullable|array',
            'items.*.addons.*.addon_id' => 'required_with:items.*.addons|integer',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $restaurant = Restaurant::query()->where('id', $request->store_id)->where('is_active', 1)->first();
        if (!$restaurant) {
            return response()->json([
                'success' => false,
                'errors' => ['Invalid or inactive store_id'],
            ], 422);
        }

        $itemIds = collect($request->items)->pluck('item_id')->unique()->values()->all();
        $menuItems = MenuItem::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereIn('id', $itemIds)
            ->get()
            ->keyBy('id');

        if (count($menuItems) !== count($itemIds)) {
            return response()->json([
                'success' => false,
                'errors' => ['One or more items do not belong to the selected restaurant'],
            ], 422);
        }

        $itemVariations = ItemVariation::query()
            ->whereIn('item_id', $itemIds)
            ->get()
            ->groupBy('item_id');

        $itemAddons = ItemAddon::query()
            ->whereIn('item_id', $itemIds)
            ->get()
            ->groupBy('item_id');

        $computedTotal = 0.0;
        $computedQuantity = 0;
        $normalizedItems = [];
        $tolerance = 0.01;

        foreach ($request->items as $item) {
            $menuItem = $menuItems->get((int) $item['item_id']);
            $quantity = (int) $item['quantity'];
            if ($quantity <= 0) {
                return response()->json([
                    'success' => false,
                    'errors' => ['Item quantity must be greater than zero'],
                ], 422);
            }

            $serverUnitPrice = (float) $menuItem->price;

            $selectedVariation = $item['selected_variation'] ?? null;
            if (!empty($selectedVariation['variation_id'])) {
                $variationRow = $itemVariations
                    ->get($menuItem->id, collect())
                    ->firstWhere('variation_id', (int) $selectedVariation['variation_id']);
                if (!$variationRow) {
                    return response()->json([
                        'success' => false,
                        'errors' => ["Invalid variation for item_id {$menuItem->id}"],
                    ], 422);
                }
                $serverUnitPrice = (float) $variationRow->web_price;
            }

            $addons = collect($item['addons'] ?? []);
            if ($addons->isNotEmpty()) {
                $addonMap = $itemAddons->get($menuItem->id, collect())->keyBy('addon_item_id');
                foreach ($addons as $addon) {
                    $addonId = (int) ($addon['addon_id'] ?? 0);
                    $mapping = $addonMap->get($addonId);
                    if (!$mapping) {
                        return response()->json([
                            'success' => false,
                            'errors' => ["Invalid addon {$addonId} for item_id {$menuItem->id}"],
                        ], 422);
                    }
                    $serverUnitPrice += (float) $mapping->web_price;
                }
            }

            $serverLineTotal = round($serverUnitPrice * $quantity, 2);
            $clientLineTotal = round((float) $item['total_price'], 2);

            if (abs($serverLineTotal - $clientLineTotal) > $tolerance) {
                return response()->json([
                    'success' => false,
                    'errors' => ["Price mismatch for item_id {$menuItem->id}"],
                ], 422);
            }

            $computedTotal += $serverLineTotal;
            $computedQuantity += $quantity;
            $normalizedItems[] = [
                'item_id' => $menuItem->id,
                'item_name' => $menuItem->name,
                'price' => $serverUnitPrice,
                'total_price' => $serverLineTotal,
                'quantity' => $quantity,
                'item_type' => $item['item_type'] ?? 0,
                'category_name' => $item['category_name'] ?? '',
                'menu_name' => $item['menu_name'] ?? '',
                'short_description' => $item['short_description'] ?? '',
                'description' => $item['description'] ?? '',
                'status' => $item['status'] ?? 0,
                'order_status' => $item['order_status'] ?? 4,
                'notes' => $item['notes'] ?? '',
                'customize_status' => $item['customize_status'] ?? 0,
                'addon_status' => $item['addon_status'] ?? 0,
                'is_meal' => $item['is_meal'] ?? 0,
            ];
        }

        $clientTotal = round((float) $request->total_price, 2);
        $computedTotal = round($computedTotal, 2);
        if (abs($computedTotal - $clientTotal) > $tolerance) {
            return response()->json([
                'success' => false,
                'errors' => ['Order total mismatch'],
            ], 422);
        }

        DB::beginTransaction();

        try {
            $orderId = $request->store_name . '_' . time() . '_' . ($request->token_number ?? 1);

            Order::create([
                'order_id' => $orderId,
                'token_number' => $request->token_number ?? 1,
                'user_id' => $request->user_id,
                'store_id' => $restaurant->id,
                'order_category' => $request->order_category,
                'order_type' => $request->order_type,
                'total_quantity' => $computedQuantity,
                'total_price' => $computedTotal,
                'delivery_address' => $request->delivery_address ?? '',
                'address_lat' => $request->address_lat ?? '',
                'address_long' => $request->address_long ?? '',
                'delivery_charges' => $request->delivery_charges ?? 0,
                'service_charge' => $request->service_charge ?? 0,
                'selectedDate' => $request->selectedDate ?? '',
                'time' => $request->time ?? '',
                'transaction_id' => $request->transaction_id ?? '',
                'ip_address' => $request->ip_address ?? '',
                'store_name' => $restaurant->name,
                'order_status' => $request->order_status ?? 4,
                'table_no' => $request->table_no ?? 0,
                'total_tax' => $request->total_tax ?? 0,
                'discount' => $request->discount ?? 0,
                'tip' => $request->tip ?? 0,
                'payment_method' => $request->payment_method ?? '',
                'print_status' => $request->print_status ?? 0,
                'order_comments' => $request->order_comments ?? '',
            ]);

            foreach ($normalizedItems as $item) {
                OrderItem::create([
                    'order_id' => $orderId,
                    'item_id' => $item['item_id'],
                    'user_id' => $request->user_id,
                    'store_id' => $restaurant->id,
                    'item_name' => $item['item_name'],
                    'price' => $item['price'],
                    'total_price' => $item['total_price'],
                    'quantity' => $item['quantity'],
                    'item_type' => $item['item_type'] ?? 0,
                    'category_name' => $item['category_name'] ?? '',
                    'menu_name' => $item['menu_name'] ?? '',
                    'short_description' => $item['short_description'] ?? '',
                    'description' => $item['description'] ?? '',
                    'status' => $item['status'] ?? 0,
                    'order_status' => $item['order_status'] ?? 4,
                    'notes' => $item['notes'] ?? '',
                    'customize_status' => $item['customize_status'] ?? 0,
                    'addon_status' => $item['addon_status'] ?? 0,
                    'is_meal' => $item['is_meal'] ?? 0,
                ]);
            }

            DB::commit();

            return response()->json(['success' => true, 'order_id' => $orderId]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'errors' => [$e->getMessage()]], 500);
        }
    }

    /**
     * Get bill summary for an order.
     */
    public function summary($order_id)
    {
        $order = Order::where('order_id', $order_id)->first();
        if (!$order) {
            return response()->json(['success' => false, 'errors' => ['Order not found']], 404);
        }

        return response()->json(['success' => true, 'order' => $order]);
    }

    /**
     * Get item-wise order history.
     */
    public function itemHistory($order_item_id)
    {
        $item = OrderItem::find($order_item_id);
        if (!$item) {
            return response()->json(['success' => false, 'errors' => ['Order item not found']], 404);
        }

        return response()->json(['success' => true, 'order_item' => $item]);
    }

    /**
     * Get all line items for an order id.
     */
    public function orderItems($order_id)
    {
        $order = Order::where('order_id', $order_id)->first();
        if (!$order) {
            return response()->json(['success' => false, 'errors' => ['Order not found']], 404);
        }

        $items = OrderItem::where('order_id', $order_id)->orderBy('id')->get();

        return response()->json([
            'success' => true,
            'order_id' => $order_id,
            'items' => $items,
        ]);
    }
}

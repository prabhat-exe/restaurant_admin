<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\OrderItem;
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
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $orderId = $request->store_name . '_' . time() . '_' . ($request->token_number ?? 1);

            Order::create([
                'order_id' => $orderId,
                'token_number' => $request->token_number ?? 1,
                'user_id' => $request->user_id,
                'store_id' => $request->store_id,
                'order_category' => $request->order_category,
                'order_type' => $request->order_type,
                'total_quantity' => $request->total_quantity,
                'total_price' => $request->total_price,
                'delivery_address' => $request->delivery_address ?? '',
                'address_lat' => $request->address_lat ?? '',
                'address_long' => $request->address_long ?? '',
                'delivery_charges' => $request->delivery_charges ?? 0,
                'service_charge' => $request->service_charge ?? 0,
                'selectedDate' => $request->selectedDate ?? '',
                'time' => $request->time ?? '',
                'transaction_id' => $request->transaction_id ?? '',
                'ip_address' => $request->ip_address ?? '',
                'store_name' => $request->store_name,
                'order_status' => $request->order_status ?? 4,
                'table_no' => $request->table_no ?? 0,
                'total_tax' => $request->total_tax ?? 0,
                'discount' => $request->discount ?? 0,
                'tip' => $request->tip ?? 0,
                'payment_method' => $request->payment_method ?? '',
                'print_status' => $request->print_status ?? 0,
                'order_comments' => $request->order_comments ?? '',
            ]);

            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id' => $orderId,
                    'item_id' => $item['item_id'],
                    'user_id' => $request->user_id,
                    'store_id' => $request->store_id,
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
}

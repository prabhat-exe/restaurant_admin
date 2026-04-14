<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class RestaurantOrderController extends Controller
{
    public function showOrders(Request $request)
    {
        $restaurantId = auth('restaurant')->user()->id;

        $orders = Order::with('user')
                        ->where('store_id', $restaurantId)
                        ->orderBy('created_at', 'desc')
                        ->get();

        $futureOrders = $orders
            ->filter(fn (Order $order) => $order->is_future_scheduled)
            ->sortBy(fn (Order $order) => $order->scheduled_at ?? $order->created_at)
            ->values();

        $currentAndPastOrders = $orders
            ->reject(fn (Order $order) => $order->is_future_scheduled)
            ->sortByDesc(fn (Order $order) => $order->display_order_at)
            ->values();

        return view('restaurant.orders', compact('futureOrders', 'currentAndPastOrders'));
    }

    public function showOrderDetails(string $orderId)
    {
        $restaurantId = auth('restaurant')->user()->id;

        $order = Order::with('user')
            ->where('store_id', $restaurantId)
            ->where('order_id', $orderId)
            ->firstOrFail();

        $orderItems = OrderItem::where('store_id', $restaurantId)
            ->where('order_id', $orderId)
            ->orderBy('id')
            ->get();

        // dd($order->user->name);
        // dd($order, $orderItems);
        return view('restaurant.order_details', compact('order', 'orderItems'));
    }
}

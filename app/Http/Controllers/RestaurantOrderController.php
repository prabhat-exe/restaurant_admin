<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RestaurantOrderController extends Controller
{
    public function showOrders(Request $request)
    {
        $restaurantId = auth('restaurant')->user()->id;

        $orders = Order::where('store_id', $restaurantId)
                        ->orderBy('created_at', 'desc')
                        ->get();

        return view('restaurant.orders', compact('orders'));
    }
}

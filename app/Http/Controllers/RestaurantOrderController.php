<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RestaurantOrderController extends Controller
{
    private const ORDER_PAGES = [
        'current' => [
            'heading' => 'Current Item Orders',
            'subheading' => 'Immediate, due-now, and past item orders.',
            'pageName' => 'current_page',
        ],
        'scheduled' => [
            'heading' => 'Scheduled Item Orders',
            'subheading' => 'Normal item orders scheduled for a future date or time.',
            'pageName' => 'scheduled_page',
        ],
        'meal-deliveries' => [
            'heading' => 'Meal Plan Delivery Schedule',
            'subheading' => 'Day-wise foods from meal plans for kitchen preparation and delivery timing.',
            'pageName' => 'meal_deliveries_page',
        ],
        'meal-packages' => [
            'heading' => 'Meal Plan Packages',
            'subheading' => 'Whole meal-plan package orders, shown once per checkout.',
            'pageName' => 'meal_packages_page',
        ],
    ];

    public function showOrders(Request $request)
    {
        return $this->showOrderPage($request, 'current');
    }

    public function showScheduledOrders(Request $request)
    {
        return $this->showOrderPage($request, 'scheduled');
    }

    public function showMealPlanDeliveries(Request $request)
    {
        return $this->showOrderPage($request, 'meal-deliveries');
    }

    public function showMealPlanPackages(Request $request)
    {
        return $this->showOrderPage($request, 'meal-packages');
    }

    private function showOrderPage(Request $request, string $orderPage)
    {
        $restaurantId = auth('restaurant')->user()->id;

        $orders = Order::with('user')
            ->where('store_id', $restaurantId)
            ->orderBy('created_at', 'desc')
            ->get();

        $singleItemOrders = $orders
            ->reject(fn (Order $order) => $order->is_meal_plan)
            ->values();

        $today = Carbon::today()->format('Y-m-d');
        $orderStats = [
            'current' => $singleItemOrders
                ->reject(fn (Order $order) => $order->is_future_scheduled)
                ->count(),
            'scheduled' => $singleItemOrders
                ->filter(fn (Order $order) => $order->is_future_scheduled)
                ->count(),
            'meal-packages' => $orders
                ->filter(fn (Order $order) => $order->is_meal_plan)
                ->count(),
            'meal-deliveries' => OrderItem::query()
                ->where('store_id', $restaurantId)
                ->where('is_meal_plan_item', true)
                ->whereNotNull('scheduled_date')
                ->where('scheduled_date', '>=', $today)
                ->count(),
        ];

        $mealPlanDeliveryQuery = OrderItem::query()
            ->where('store_id', $restaurantId)
            ->where('is_meal_plan_item', true)
            ->whereNotNull('scheduled_date')
            ->where('scheduled_date', '>=', $today);

        $pageMeta = self::ORDER_PAGES[$orderPage];
        $currentAndPastItemOrders = null;
        $scheduledItemOrders = null;
        $mealPlanOrders = null;
        $mealPlanDeliveryItems = null;

        if ($orderPage === 'current') {
            $currentAndPastItemOrders = $this->paginateCollection(
                $singleItemOrders
                ->reject(fn (Order $order) => $order->is_future_scheduled)
                ->sortByDesc(fn (Order $order) => $order->display_order_at)
                ->values(),
                $request,
                $pageMeta['pageName']
            );
        }

        if ($orderPage === 'scheduled') {
            $scheduledItemOrders = $this->paginateCollection(
                $singleItemOrders
                ->filter(fn (Order $order) => $order->is_future_scheduled)
                ->sortBy(fn (Order $order) => $order->scheduled_at ?? $order->created_at)
                ->values(),
                $request,
                $pageMeta['pageName']
            );
        }

        if ($orderPage === 'meal-packages') {
            $mealPlanOrders = $this->paginateCollection(
                $orders
                ->filter(fn (Order $order) => $order->is_meal_plan)
                ->sortBy(fn (Order $order) => $order->plan_start_date ?? $order->scheduled_at ?? $order->created_at)
                ->values(),
                $request,
                $pageMeta['pageName']
            );
        }

        if ($orderPage === 'meal-deliveries') {
            $mealPlanDeliveryItems = $mealPlanDeliveryQuery
                ->orderBy('scheduled_date')
                ->orderBy('scheduled_time')
                ->orderBy('meal_slot')
                ->orderBy('id')
                ->paginate(10, ['*'], $pageMeta['pageName'])
                ->withQueryString();
        }

        $mealPlanOrderMap = $orders
            ->where('is_meal_plan', true)
            ->keyBy('order_id');

        return view('restaurant.orders', compact(
            'orderPage',
            'pageMeta',
            'scheduledItemOrders',
            'currentAndPastItemOrders',
            'mealPlanOrders',
            'mealPlanDeliveryItems',
            'mealPlanOrderMap',
            'orderStats'
        ));
    }

    private function paginateCollection(Collection $items, Request $request, string $pageName, int $perPage = 10): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage($pageName);
        $pageItems = $items->forPage($page, $perPage)->values();

        return (new LengthAwarePaginator(
            $pageItems,
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'pageName' => $pageName,
            ]
        ))->appends($request->except($pageName));
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

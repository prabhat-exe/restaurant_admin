<?php

namespace App\Http\Controllers\Api;

use App\Models\MenuItem;
use App\Models\ItemAddon;
use App\Models\ItemVariation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Models\User;
use App\Models\Variation;
use Carbon\Carbon;
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
        $authUser = $this->resolveUserFromRequest($request);
        if (!$authUser) {
            return response()->json([
                'success' => false,
                'errors' => ['Unauthorized. Please login first.'],
            ], 401);
        }

        if ($request->filled('user_id') && (int) $request->user_id !== (int) $authUser->id) {
            return response()->json([
                'success' => false,
                'errors' => ['User mismatch. Please login again.'],
            ], 403);
        }

        $isMealPlan = $request->boolean('is_meal_plan');

        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|integer',
            'store_id' => 'required|integer',
            'store_name' => 'required|string',
            'order_category' => 'required|integer',
            'order_type' => 'required|integer',
            'total_quantity' => 'required|integer',
            'total_price' => 'required|numeric',
            'selectedDate' => 'nullable|string',
            'time' => 'nullable|string',
            'pre_order_status' => 'nullable|integer',
            'delivery_address' => 'required|string|max:1000',
            'address_lat' => 'required|numeric|between:-90,90',
            'address_long' => 'required|numeric|between:-180,180',
            'is_meal_plan' => 'nullable|boolean',
            'plan_type' => 'nullable|string|max:100',
            'days_per_week' => 'nullable|integer|min:1|max:7',
            'total_plan_days' => 'nullable|integer|min:1|max:366',
            'start_date' => 'nullable|date',
            'meal_slot_times' => 'nullable|array',
            'meal_plan_summary' => 'nullable|array',
            'items' => 'nullable|array',
            'items.*.item_id' => 'required_with:items|integer',
            'items.*.item_name' => 'required_with:items|string',
            'items.*.price' => 'required_with:items|numeric',
            'items.*.total_price' => 'required_with:items|numeric',
            'items.*.quantity' => 'required_with:items|integer',
            'items.*.selected_variation.variation_id' => 'nullable|integer',
            'items.*.addons' => 'nullable|array',
            'items.*.addons.*.addon_id' => 'required_with:items.*.addons|integer',
            'schedule' => 'nullable|array',
            'schedule.*.scheduled_date' => 'required_with:schedule|date',
            'schedule.*.scheduled_time' => 'required_with:schedule|string',
            'schedule.*.plan_day_number' => 'required_with:schedule|integer|min:1',
            'schedule.*.plan_week_number' => 'required_with:schedule|integer|min:1',
            'schedule.*.meal_slot' => 'required_with:schedule|string|max:100',
            'schedule.*.item_id' => 'required_with:schedule|integer',
            'schedule.*.item_name' => 'required_with:schedule|string',
            'schedule.*.price' => 'required_with:schedule|numeric',
            'schedule.*.total_price' => 'required_with:schedule|numeric',
            'schedule.*.quantity' => 'required_with:schedule|integer',
            'schedule.*.selected_variation.variation_id' => 'nullable|integer',
            'schedule.*.addons' => 'nullable|array',
            'schedule.*.addons.*.addon_id' => 'required_with:schedule.*.addons|integer',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        if ($isMealPlan && count($request->input('schedule', [])) < 1) {
            return response()->json(['success' => false, 'errors' => ['Meal plan schedule is required']], 422);
        }

        if (!$isMealPlan && count($request->input('items', [])) < 1) {
            return response()->json(['success' => false, 'errors' => ['Items are required']], 422);
        }

        $restaurant = Restaurant::query()->where('id', $request->store_id)->where('is_active', 1)->first();
        if (!$restaurant) {
            return response()->json([
                'success' => false,
                'errors' => ['Invalid or inactive store_id'],
            ], 422);
        }

        if (
            $restaurant->latitude === null ||
            $restaurant->longitude === null ||
            $restaurant->delivery_radius_km === null ||
            (float) $restaurant->delivery_radius_km <= 0
        ) {
            return response()->json([
                'success' => false,
                'errors' => ['This restaurant has not configured its delivery radius yet.'],
            ], 422);
        }

        $deliveryDistanceKm = $this->calculateDistanceKm(
            (float) $restaurant->latitude,
            (float) $restaurant->longitude,
            (float) $request->address_lat,
            (float) $request->address_long
        );

        if ($deliveryDistanceKm > (float) $restaurant->delivery_radius_km) {
            return response()->json([
                'success' => false,
                'errors' => ['Selected delivery address is outside the restaurant delivery radius.'],
                'meta' => [
                    'delivery_distance_km' => round($deliveryDistanceKm, 2),
                    'delivery_radius_km' => (float) $restaurant->delivery_radius_km,
                ],
            ], 422);
        }

        $checkoutLines = $isMealPlan ? $request->input('schedule', []) : $request->input('items', []);
        $itemIds = collect($checkoutLines)->pluck('item_id')->unique()->values()->all();
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

        $variationIds = $itemVariations->flatten()->pluck('variation_id')->unique()->values()->all();
        $variationsById = Variation::query()
            ->whereIn('id', $variationIds)
            ->get(['id', 'variation_name'])
            ->keyBy('id');

        $itemAddons = ItemAddon::query()
            ->whereIn('item_id', $itemIds)
            ->get()
            ->groupBy('item_id');

        $addonIds = $itemAddons->flatten()->pluck('addon_item_id')->unique()->values()->all();
        $addonItemsById = MenuItem::query()
            ->whereIn('id', $addonIds)
            ->get(['id', 'name'])
            ->keyBy('id');

        $computedTotal = 0.0;
        $computedQuantity = 0;
        $normalizedItems = [];
        $tolerance = 0.01;

        foreach ($checkoutLines as $item) {
            $menuItem = $menuItems->get((int) $item['item_id']);
            $quantity = (int) $item['quantity'];
            if ($quantity <= 0) {
                return response()->json([
                    'success' => false,
                    'errors' => ['Item quantity must be greater than zero'],
                ], 422);
            }

            $serverUnitPrice = (float) $menuItem->price;
            $selectedVariationPayload = null;
            $addonPayload = [];

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
                $variationMeta = $variationsById->get((int) $selectedVariation['variation_id']);
                $selectedVariationPayload = [
                    'variation_id' => (int) $variationRow->variation_id,
                    'variation_name' => $variationMeta?->variation_name ?? ($selectedVariation['variation_name'] ?? 'Variation'),
                    'variation_price' => $serverUnitPrice,
                ];
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
                    $addonPrice = (float) $mapping->web_price;
                    $serverUnitPrice += $addonPrice;
                    $addonItem = $addonItemsById->get($addonId);
                    $addonPayload[] = [
                        'addon_id' => $addonId,
                        'addon_name' => $addonItem?->name ?? ($addon['addon_name'] ?? 'Addon'),
                        'price' => $addonPrice,
                    ];
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

            $scheduledDate = null;
            $scheduledTime = null;
            if ($isMealPlan) {
                try {
                    $scheduledDate = Carbon::parse($item['scheduled_date'] ?? '')->format('Y-m-d');
                    $scheduledTime = Carbon::parse($scheduledDate . ' ' . ($item['scheduled_time'] ?? ''))->format('H:i:s');
                } catch (\Throwable $e) {
                    return response()->json([
                        'success' => false,
                        'errors' => ["Invalid schedule date/time for item_id {$menuItem->id}"],
                    ], 422);
                }
            }

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
                'selected_variation_json' => $selectedVariationPayload,
                'addons_json' => $addonPayload,
                'is_meal' => $item['is_meal'] ?? 0,
                'is_meal_plan_item' => $isMealPlan,
                'scheduled_date' => $scheduledDate,
                'scheduled_time' => $scheduledTime,
                'plan_day_number' => $isMealPlan ? (int) ($item['plan_day_number'] ?? 0) : null,
                'plan_week_number' => $isMealPlan ? (int) ($item['plan_week_number'] ?? 0) : null,
                'meal_slot' => $isMealPlan ? ($item['meal_slot'] ?? '') : null,
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

        $selectedDate = trim((string) ($request->selectedDate ?? ''));
        $selectedTime = trim((string) ($request->time ?? ''));
        $preOrderStatus = 0;
        $scheduledAt = null;
        $planStartDate = null;
        $planEndDate = null;

        if ($isMealPlan) {
            $scheduledDates = collect($normalizedItems)
                ->pluck('scheduled_date')
                ->filter()
                ->sort()
                ->values();
            $uniqueScheduledDates = $scheduledDates->unique()->values();
            $expectedPlanDays = (int) ($request->total_plan_days ?? 0);
            $daysPerWeek = (int) ($request->days_per_week ?? 5);

            if ($expectedPlanDays > 0 && $uniqueScheduledDates->count() !== $expectedPlanDays) {
                return response()->json([
                    'success' => false,
                    'errors' => ['Meal plan schedule does not match total_plan_days'],
                ], 422);
            }

            if ($daysPerWeek === 5) {
                $weekendDate = $uniqueScheduledDates->first(function ($date) {
                    return Carbon::parse($date)->isWeekend();
                });

                if ($weekendDate) {
                    return response()->json([
                        'success' => false,
                        'errors' => ['5 days/week meal plans can only be scheduled on weekdays'],
                    ], 422);
                }
            }

            $planStartDate = $scheduledDates->first();
            $planEndDate = $scheduledDates->last();
            $selectedDate = $planStartDate ?: trim((string) ($request->start_date ?? ''));
            $selectedTime = '';
            if ($selectedDate !== '') {
                $preOrderStatus = Carbon::parse($selectedDate)->endOfDay()->greaterThan(Carbon::now()) ? 1 : 0;
            }
        }

        if (!$isMealPlan && ($selectedDate !== '' || $selectedTime !== '')) {
            try {
                $scheduledAt = $this->parseScheduledAt($selectedDate, $selectedTime);
                $selectedDate = $scheduledAt->format('Y-m-d');
                $selectedTime = $selectedTime !== '' ? $scheduledAt->format('H:i:s') : '';
                $preOrderStatus = $scheduledAt->greaterThan(Carbon::now()) ? 1 : 0;
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'errors' => ['Invalid selectedDate/time for preorder'],
                ], 422);
            }
        }

        DB::beginTransaction();

        try {
            $orderId = $this->generateOrderId($restaurant, (int) ($request->token_number ?? 1));

            Order::create([
                'order_id' => $orderId,
                'token_number' => $request->token_number ?? 1,
                'user_id' => $authUser->id,
                'store_id' => $restaurant->id,
                'order_category' => $request->order_category,
                'order_type' => $request->order_type,
                'total_quantity' => $computedQuantity,
                'total_price' => $computedTotal,
                'delivery_address' => $request->delivery_address ?? '',
                'address_lat' => $request->address_lat ?? '',
                'address_long' => $request->address_long ?? '',
                'distance' => round($deliveryDistanceKm, 2),
                'delivery_charges' => $request->delivery_charges ?? 0,
                'service_charge' => $request->service_charge ?? 0,
                'selectedDate' => $selectedDate,
                'time' => $selectedTime,
                'pre_order_status' => $preOrderStatus,
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
                'is_meal_plan' => $isMealPlan,
                'plan_type' => $isMealPlan ? ($request->plan_type ?? '') : null,
                'plan_start_date' => $isMealPlan ? $planStartDate : null,
                'plan_end_date' => $isMealPlan ? $planEndDate : null,
                'days_per_week' => $isMealPlan ? ($request->days_per_week ?? 5) : null,
                'plan_total_days' => $isMealPlan ? ($request->total_plan_days ?? collect($normalizedItems)->pluck('scheduled_date')->unique()->count()) : null,
                'meal_plan_summary_json' => $isMealPlan ? [
                    'meal_slot_times' => $request->meal_slot_times ?? [],
                    'summary' => $request->meal_plan_summary ?? [],
                ] : null,
            ]);

            foreach ($normalizedItems as $item) {
                OrderItem::create([
                    'order_id' => $orderId,
                    'item_id' => $item['item_id'],
                    'user_id' => $authUser->id,
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
                    'selected_variation_json' => $item['selected_variation_json'] ?? null,
                    'addons_json' => $item['addons_json'] ?? [],
                    'is_meal' => $item['is_meal'] ?? 0,
                    'is_meal_plan_item' => $item['is_meal_plan_item'] ?? false,
                    'scheduled_date' => $item['scheduled_date'] ?? ($selectedDate ?: null),
                    'scheduled_time' => $item['scheduled_time'] ?? ($selectedTime ?: null),
                    'plan_day_number' => $item['plan_day_number'] ?? null,
                    'plan_week_number' => $item['plan_week_number'] ?? null,
                    'meal_slot' => $item['meal_slot'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json(['success' => true, 'order_id' => $orderId]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'errors' => [$e->getMessage()]], 500);
        }
    }

    private function parseScheduledAt(string $selectedDate, string $selectedTime): Carbon
    {
        $normalizedDate = trim($selectedDate);
        $normalizedTime = trim($selectedTime);

        if ($normalizedDate === '') {
            $normalizedDate = Carbon::now()->format('Y-m-d');
        } else {
            $normalizedDate = Carbon::parse($normalizedDate)->format('Y-m-d');
        }

        if ($normalizedTime === '') {
            return Carbon::parse($normalizedDate)->endOfDay();
        }

        return Carbon::parse($normalizedDate . ' ' . $normalizedTime);
    }

    private function generateOrderId(Restaurant $restaurant, int $tokenNumber): string
    {
        $prefix = 'R' . $restaurant->id;
        $date = Carbon::now()->format('ymd');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $suffix = strtoupper(bin2hex(random_bytes(2)));
            $orderId = sprintf('%s-%s-%s', $prefix, $date, $suffix);

            if (!Order::withTrashed()->where('order_id', $orderId)->exists()) {
                return $orderId;
            }
        }

        return sprintf('%s-%s-%s', $prefix, $date, strtoupper(bin2hex(random_bytes(3))));
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

    /**
     * Get upcoming day-wise orders for the logged-in customer.
     */
    public function future(Request $request)
    {
        $authUser = $this->resolveUserFromRequest($request);
        if (!$authUser) {
            return response()->json([
                'success' => false,
                'errors' => ['Unauthorized. Please login first.'],
            ], 401);
        }

        $today = Carbon::today()->format('Y-m-d');
        $type = str_replace('_', '-', strtolower((string) $request->query('type', 'all')));

        $itemsQuery = OrderItem::query()
            ->where('user_id', $authUser->id)
            ->whereNotNull('scheduled_date')
            ->where('scheduled_date', '>', $today);

        if (in_array($type, ['meal-plan', 'meal'], true)) {
            $itemsQuery->where('is_meal_plan_item', true);
        } elseif (in_array($type, ['item', 'scheduled-item', 'pick-your-item'], true)) {
            $itemsQuery->where('is_meal_plan_item', false);
        }

        $items = $itemsQuery
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->orderBy('plan_day_number')
            ->get();

        return response()->json([
            'success' => true,
            'orders' => $this->groupOrderItemsForCustomerView($items),
        ]);
    }

    /**
     * Get same-day orders placed by the logged-in customer.
     */
    public function sameDay(Request $request)
    {
        $authUser = $this->resolveUserFromRequest($request);
        if (!$authUser) {
            return response()->json([
                'success' => false,
                'errors' => ['Unauthorized. Please login first.'],
            ], 401);
        }

        $today = Carbon::today()->format('Y-m-d');

        $items = OrderItem::query()
            ->where('user_id', $authUser->id)
            ->where('is_meal_plan_item', false)
            ->where(function ($query) use ($today) {
                $query->where('scheduled_date', $today)
                    ->orWhere(function ($createdToday) use ($today) {
                        $createdToday->whereNull('scheduled_date')
                            ->whereDate('created_at', $today);
                    });
            })
            ->orderBy('created_at', 'desc')
            ->orderBy('scheduled_time')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'orders' => $this->groupOrderItemsForCustomerView($items),
        ]);
    }

    private function groupOrderItemsForCustomerView($items)
    {
        $orders = Order::query()
            ->whereIn('order_id', $items->pluck('order_id')->unique()->values())
            ->get()
            ->keyBy('order_id');

        return $items
            ->groupBy(function (OrderItem $item) use ($orders) {
                $scheduledDate = $item->scheduled_date?->format('Y-m-d') ?? (string) $item->scheduled_date;
                if ($scheduledDate !== '') {
                    return $scheduledDate;
                }

                return $orders->get($item->order_id)?->created_at?->format('Y-m-d')
                    ?? $item->created_at?->format('Y-m-d')
                    ?? 'Today';
            })
            ->map(function ($dayItems, $date) use ($orders) {
                return [
                    'date' => $date,
                    'items' => $dayItems->map(function (OrderItem $item) use ($orders) {
                        $order = $orders->get($item->order_id);

                        return [
                            'order_id' => $item->order_id,
                            'store_name' => $order?->store_name,
                            'order_kind' => $item->is_meal_plan_item ? 'meal_plan' : 'scheduled_item',
                            'is_meal_plan_item' => (bool) $item->is_meal_plan_item,
                            'scheduled_date' => $item->scheduled_date?->format('Y-m-d') ?? (string) $item->scheduled_date,
                            'scheduled_time' => $item->scheduled_time,
                            'plan_day_number' => $item->plan_day_number,
                            'plan_week_number' => $item->plan_week_number,
                            'meal_slot' => $item->meal_slot,
                            'item_name' => $item->item_name,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'total_price' => $item->total_price,
                            'order_status' => $item->order_status,
                            'selected_variation' => $item->selected_variation_json,
                            'addons' => $item->addons_json,
                        ];
                    })->values(),
                ];
            })
            ->values();
    }

    private function resolveUserFromRequest(Request $request): ?User
    {
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $token = trim(substr($authHeader, 7));
            if ($token !== '') {
                return User::where('api_token', $token)->first();
            }
        }

        return null;
    }

    private function calculateDistanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        return $earthRadiusKm * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}

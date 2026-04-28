@php
    $title = 'Restaurant Orders';
    $panelName = 'Restaurant Panel';
    $heading = $pageMeta['heading'] ?? 'Orders List';
    $subheading = $pageMeta['subheading'] ?? 'Normal orders, scheduled item orders, and meal-plan deliveries are separated';
    $logoutRoute = 'restaurant.logout';
    $navLinks = [
        ['label' => 'Items', 'route' => 'restaurant.dashboard'],
        ['label' => 'Delivery', 'route' => 'restaurant.delivery'],
        ['label' => 'Setting', 'route' => 'restaurant.settings'],
        ['label' => 'Orders', 'route' => 'restaurant.orders', 'active' => 'restaurant.orders*'],
        ['label' => 'Menu Import', 'route' => 'menu.import.form'],
    ];
@endphp

@extends('layouts.panel')

@section('content')
    @php
        $renderBadge = function ($label, $classes) {
            return '<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ' . e($classes) . '">' . e($label) . '</span>';
        };

        $renderPagination = function ($paginator) {
            if (!$paginator->hasPages()) {
                return '';
            }

            return '<div class="border-t border-gray-100 px-4 py-3 dark:border-gray-800">' . $paginator->links() . '</div>';
        };

        $renderOrderTable = function ($orders, $emptyMessage, $dateHeading, $dateValueResolver, $badgeResolver) use ($currencySymbol, $renderBadge) {
            if ($orders->count() === 0) {
                return '<div class="px-4 py-5 text-sm text-warning-700">' . e($emptyMessage) . '</div>';
            }

            $html = '<table class="min-w-full divide-y divide-gray-200 text-sm">';
            $html .= '<thead class="bg-gray-50 dark:bg-gray-800"><tr>';
            $html .= '<th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">#</th>';
            $html .= '<th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Order ID</th>';
            $html .= '<th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Customer</th>';
            $html .= '<th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Amount</th>';
            $html .= '<th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Type</th>';
            $html .= '<th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">' . e($dateHeading) . '</th>';
            $html .= '<th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Placed At</th>';
            $html .= '<th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Action</th>';
            $html .= '</tr></thead><tbody class="divide-y divide-gray-100">';

            $startIndex = $orders->firstItem() ?? 1;
            foreach ($orders as $index => $order) {
                [$badgeLabel, $badgeClasses] = $badgeResolver($order);
                $customerName = $order->user->name ?? 'Guest User';
                $placedAt = $order->created_at?->format('d M Y h:i A') ?? '-';

                $html .= '<tr class="align-top">';
                $html .= '<td class="px-4 py-3">' . ($startIndex + $index) . '</td>';
                $html .= '<td class="break-words px-4 py-3 font-medium text-gray-900 dark:text-gray-100">' . e($order->order_id) . '</td>';
                $html .= '<td class="px-4 py-3">' . e($customerName) . '</td>';
                $html .= '<td class="px-4 py-3">' . e($currencySymbol) . ' ' . e($order->total_price ?? 0) . '</td>';
                $html .= '<td class="px-4 py-3">' . $renderBadge($badgeLabel, $badgeClasses) . '</td>';
                $html .= '<td class="px-4 py-3">' . $dateValueResolver($order) . '</td>';
                $html .= '<td class="px-4 py-3">' . e($placedAt) . '</td>';
                $html .= '<td class="px-4 py-3"><a href="' . e(route('restaurant.orders.details', $order->order_id)) . '" class="inline-flex rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-600">View</a></td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
            return $html;
        };

        $renderMealPlanDeliveryTable = function ($items) use ($currencySymbol, $mealPlanOrderMap, $renderBadge) {
            if ($items->count() === 0) {
                return '<div class="px-4 py-5 text-sm text-warning-700">No upcoming meal-plan deliveries found.</div>';
            }

            $html = '<table class="min-w-full divide-y divide-gray-200 text-sm">';
            $html .= '<thead class="bg-gray-50 dark:bg-gray-800"><tr>';
            $html .= '<th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Date & Time</th>';
            $html .= '<th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Meal Slot</th>';
            $html .= '<th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Item</th>';
            $html .= '<th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Qty</th>';
            $html .= '<th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Customer</th>';
            $html .= '<th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Plan Day</th>';
            $html .= '<th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Order</th>';
            $html .= '</tr></thead><tbody class="divide-y divide-gray-100">';

            $startIndex = $items->firstItem() ?? 1;
            foreach ($items as $item) {
                $order = $mealPlanOrderMap->get($item->order_id);
                $date = $item->scheduled_date ? $item->scheduled_date->format('d M Y') : '-';
                $time = $item->scheduled_time ? \Carbon\Carbon::parse($item->scheduled_time)->format('h:i A') : '-';
                $customerName = $order?->user?->name ?? 'Guest User';
                $dayLabel = $item->plan_day_number
                    ? 'Week ' . ($item->plan_week_number ?? '-') . ', Day ' . $item->plan_day_number
                    : '-';

                $html .= '<tr class="align-top">';
                $html .= '<td class="px-4 py-3"><div class="text-xs text-gray-500">#' . e($startIndex) . '</div><div class="font-medium text-gray-900 dark:text-gray-100">' . e($date) . '</div><div class="text-xs text-gray-500">' . e($time) . '</div></td>';
                $html .= '<td class="px-4 py-3">' . $renderBadge($item->meal_slot ?: 'Meal', 'bg-purple-100 text-purple-700') . '</td>';
                $html .= '<td class="px-4 py-3"><div class="font-medium text-gray-900 dark:text-gray-100">' . e($item->item_name) . '</div><div class="text-xs text-gray-500">' . e($currencySymbol) . ' ' . e($item->total_price) . '</div></td>';
                $html .= '<td class="px-4 py-3">' . e($item->quantity) . '</td>';
                $html .= '<td class="px-4 py-3">' . e($customerName) . '</td>';
                $html .= '<td class="px-4 py-3">' . e($dayLabel) . '</td>';
                $html .= '<td class="px-4 py-3"><a href="' . e(route('restaurant.orders.details', $item->order_id)) . '" class="font-semibold text-brand-600 hover:text-brand-700">' . e($item->order_id) . '</a></td>';
                $html .= '</tr>';
                $startIndex++;
            }

            $html .= '</tbody></table>';
            return $html;
        };
    @endphp

    <div class="space-y-6">
        @php
            $orderTabs = [
                ['key' => 'current', 'label' => 'Items', 'route' => 'restaurant.orders', 'count' => $orderStats['current'] ?? 0],
                ['key' => 'scheduled', 'label' => 'Scheduled Items', 'route' => 'restaurant.orders.scheduled', 'count' => $orderStats['scheduled'] ?? 0],
                ['key' => 'meal-deliveries', 'label' => 'Meal Deliveries', 'route' => 'restaurant.orders.meal-deliveries', 'count' => $orderStats['meal-deliveries'] ?? 0],
                ['key' => 'meal-packages', 'label' => 'Meal Packages', 'route' => 'restaurant.orders.meal-packages', 'count' => $orderStats['meal-packages'] ?? 0],
            ];
        @endphp

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($orderTabs as $tab)
                @php $isActiveTab = $orderPage === $tab['key']; @endphp
                <a href="{{ route($tab['route']) }}"
                   class="rounded-xl border px-4 py-3 transition {{ $isActiveTab ? 'border-brand-200 bg-brand-50 text-brand-700 dark:border-brand-500/40 dark:bg-brand-500/10 dark:text-brand-300' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/5' }}">
                    <div class="text-xs font-semibold uppercase">{{ $tab['label'] }}</div>
                    <div class="mt-2 text-2xl font-bold">{{ $tab['count'] }}</div>
                </a>
            @endforeach
        </div>

        @if($orderPage === 'current')
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-4 py-4 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Item Orders</h3>
                <p class="mt-1 text-sm text-gray-500">Normal pick-your-item orders that are immediate, due now, or already past due. Meal-plan rows are not shown here.</p>
            </div>
            <div class="overflow-x-auto">
                {!! $renderOrderTable(
                    $currentAndPastItemOrders,
                    'No current item orders found.',
                    'Due At',
                    fn ($order) => e(($order->scheduled_at ?? $order->created_at)?->format('d M Y h:i A') ?? '-'),
                    fn ($order) => [$order->order_status == 4 ? 'Placed' : 'In Progress', 'bg-success-100 text-success-700']
                ) !!}
            </div>
            {!! $renderPagination($currentAndPastItemOrders) !!}
        </div>
        @endif

        @if($orderPage === 'scheduled')
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-4 py-4 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Scheduled Item Orders</h3>
                <p class="mt-1 text-sm text-gray-500">Only normal item orders scheduled for a future date or time. Whole meal plans are kept out of this section.</p>
            </div>
            <div class="overflow-x-auto">
                {!! $renderOrderTable(
                    $scheduledItemOrders,
                    'No scheduled item orders found.',
                    'Scheduled For',
                    fn ($order) => e($order->scheduled_at ? $order->scheduled_at->format('d M Y h:i A') : '-'),
                    fn ($order) => ['Scheduled Item Order', 'bg-brand-100 text-brand-700']
                ) !!}
            </div>
            {!! $renderPagination($scheduledItemOrders) !!}
        </div>
        @endif

        @if($orderPage === 'meal-deliveries')
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-4 py-4 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Meal Plan Delivery Schedule</h3>
                <p class="mt-1 text-sm text-gray-500">Day-wise foods from meal plans. Use this for kitchen preparation and future meal delivery timing.</p>
            </div>
            <div class="overflow-x-auto">
                {!! $renderMealPlanDeliveryTable($mealPlanDeliveryItems) !!}
            </div>
            {!! $renderPagination($mealPlanDeliveryItems) !!}
        </div>
        @endif

        @if($orderPage === 'meal-packages')
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-4 py-4 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Meal Plan Packages</h3>
                <p class="mt-1 text-sm text-gray-500">Whole meal-plan orders, shown once per checkout. These are package records, not single delivery rows.</p>
            </div>
            <div class="overflow-x-auto">
                {!! $renderOrderTable(
                    $mealPlanOrders,
                    'No meal plan packages found.',
                    'Plan Period',
                    fn ($order) => e(($order->plan_start_date ? $order->plan_start_date->format('d M Y') : '-') . ' to ' . ($order->plan_end_date ? $order->plan_end_date->format('d M Y') : '-')),
                    fn ($order) => [($order->plan_total_days ?? 0) . ' Day Meal Plan', 'bg-purple-100 text-purple-700']
                ) !!}
            </div>
            {!! $renderPagination($mealPlanOrders) !!}
        </div>
        @endif
    </div>
@endsection

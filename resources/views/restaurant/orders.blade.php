@php
    $title = 'Restaurant Orders';
    $panelName = 'Restaurant Panel';
    $heading = 'Orders List';
    $subheading = 'Track incoming and completed orders';
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
        $renderOrdersTable = function ($orders, $emptyMessage, $showScheduleBadge = false) use ($currencySymbol) {
            if ($orders->count() === 0) {
                return '<div class="px-4 py-5 text-sm text-warning-700">' . e($emptyMessage) . '</div>';
            }

            $html = '<table class="min-w-full divide-y divide-gray-200 text-sm">';
            $html .= '<thead class="bg-gray-50 dark:bg-gray-800"><tr>';
            $html .= '<th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">#</th>';
            $html .= '<th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Order ID</th>';
            $html .= '<th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Customer</th>';
            $html .= '<th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Total Amount</th>';
            $html .= '<th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Status</th>';
            $html .= '<th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Scheduled For</th>';
            $html .= '<th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Placed At</th>';
            $html .= '<th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Action</th>';
            $html .= '</tr></thead><tbody class="divide-y divide-gray-100">';

            foreach ($orders as $index => $order) {
                $statusLabel = $order->order_status == 4 ? 'Placed' : 'In Progress';
                $scheduledFor = $order->scheduled_at ? $order->scheduled_at->format('d M Y h:i A') : '-';
                $placedAt = $order->created_at?->format('d M Y h:i A') ?? '-';
                $customerName = $order->user->name ?? 'Guest User';

                $html .= '<tr>';
                $html .= '<td class="px-4 py-3">' . ($index + 1) . '</td>';
                $html .= '<td class="px-4 py-3">' . e($order->order_id) . '</td>';
                $html .= '<td class="px-4 py-3">' . e($customerName) . '</td>';
                $html .= '<td class="px-4 py-3">' . e($currencySymbol) . ' ' . e($order->total_price ?? 0) . '</td>';
                $html .= '<td class="px-4 py-3">';
                if ($showScheduleBadge && $order->is_future_scheduled) {
                    $html .= '<span class="inline-flex rounded-full bg-brand-100 px-2.5 py-1 text-xs font-semibold text-brand-700">Scheduled</span>';
                } else {
                    $html .= '<span class="inline-flex rounded-full bg-success-100 px-2.5 py-1 text-xs font-semibold text-success-700">' . e($statusLabel) . '</span>';
                }
                $html .= '</td>';
                $html .= '<td class="px-4 py-3">' . e($scheduledFor) . '</td>';
                $html .= '<td class="px-4 py-3">' . e($placedAt) . '</td>';
                $html .= '<td class="px-4 py-3"><a href="' . e(route('restaurant.orders.details', $order->order_id)) . '" class="inline-flex rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-600">View Order</a></td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
            return $html;
        };
    @endphp

    <div class="space-y-6">
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-4 py-4 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Future Scheduled Orders</h3>
                <p class="mt-1 text-sm text-gray-500">Orders scheduled for a future date or time.</p>
            </div>
            <div class="overflow-x-auto">
                {!! $renderOrdersTable($futureOrders, 'No future scheduled orders found.', true) !!}
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-4 py-4 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Current and Past Orders</h3>
                <p class="mt-1 text-sm text-gray-500">Immediate orders and scheduled orders whose time has already arrived.</p>
            </div>
            <div class="overflow-x-auto">
                {!! $renderOrdersTable($currentAndPastOrders, 'No current or past orders found.') !!}
            </div>
        </div>
    </div>
@endsection

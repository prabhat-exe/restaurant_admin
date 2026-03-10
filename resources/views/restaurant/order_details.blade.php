@php
    $title = 'Order Details';
    $panelName = 'Restaurant Panel';
    $heading = 'Order Details';
    $subheading = 'Complete details for selected order';
    $logoutRoute = 'restaurant.logout';
    $navLinks = [
        ['label' => 'Items', 'route' => 'restaurant.dashboard'],
        ['label' => 'Orders', 'route' => 'restaurant.orders', 'active' => 'restaurant.orders*'],
        ['label' => 'POS', 'route' => 'restaurant.pos'],
        ['label' => 'Menu Import', 'route' => 'menu.import.form'],
    ];
@endphp

@extends('layouts.panel')

@section('content')
    <div class="mb-4">
        <a href="{{ route('restaurant.orders') }}"
           class="inline-flex items-center rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
            Back to Orders
        </a>
    </div>

    <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="text-xs text-gray-500">Order ID</p>
                <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $order->order_id }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Customer</p>
                <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $order->customer_name ?? ('User #' . ($order->user_id ?? 'N/A')) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Total Amount</p>
                <p class="font-semibold text-gray-800 dark:text-gray-200">Rs {{ $order->total_price ?? 0 }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Order Date</p>
                <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $order->created_at->format('d M Y h:i A') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Status</p>
                <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $order->order_status == 4 ? 'Placed' : 'In Progress' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Payment Method</p>
                <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $order->payment_method ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Total Quantity</p>
                <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $order->total_quantity ?? 0 }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Table No</p>
                <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $order->table_no ?? '-' }}</p>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="overflow-x-auto">
            @if($orderItems->count() > 0)
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">#</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Item Name</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Category</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Qty</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Price</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Total</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Notes</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($orderItems as $index => $item)
                            <tr>
                                <td class="px-4 py-3">{{ $index + 1 }}</td>
                                <td class="px-4 py-3 font-medium">{{ $item->item_name }}</td>
                                <td class="px-4 py-3">{{ $item->category_name ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $item->quantity }}</td>
                                <td class="px-4 py-3">Rs {{ $item->price }}</td>
                                <td class="px-4 py-3">Rs {{ $item->total_price }}</td>
                                <td class="px-4 py-3">{{ $item->notes ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full bg-success-100 px-2.5 py-1 text-xs font-semibold text-success-700">
                                        {{ $item->order_status == 4 ? 'Placed' : 'In Progress' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="px-4 py-5 text-sm text-warning-700">No order items found for this order.</div>
            @endif
        </div>
    </div>
@endsection

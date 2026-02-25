@php
    $title = 'Restaurant Orders';
    $panelName = 'Restaurant Panel';
    $heading = 'Order List';
    $subheading = 'Track incoming and completed orders';
    $logoutRoute = 'restaurant.logout';
    $navLinks = [
        ['label' => 'Items', 'route' => 'restaurant.dashboard'],
        ['label' => 'Orders', 'route' => 'restaurant.orders'],
        ['label' => 'Menu Import', 'route' => 'menu.import.form'],
    ];
@endphp

@extends('layouts.panel')

@section('content')
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="overflow-x-auto">
            @if($orders->count() > 0)
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800 ">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">#</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Order ID</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Customer Name</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Total Amount</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Status</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($orders as $index => $order)
                            <tr>
                                <td class="px-4 py-3">{{ $index + 1 }}</td>
                                <td class="px-4 py-3 ">{{ $order->order_id }}</td>
                                <td class="px-4 py-3">{{ $order->customer_name ?? 'N/A' }}</td>
                                <td class="px-4 py-3">Rs {{ $order->total_price ?? 0 }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full bg-success-100 px-2.5 py-1 text-xs font-semibold text-success-700">
                                        {{ $order->status ?? 'Placed' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ $order->created_at->format('d M Y h:i A') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="px-4 py-5 text-sm text-warning-700">No Orders Found</div>
            @endif
        </div>
    </div>
@endsection

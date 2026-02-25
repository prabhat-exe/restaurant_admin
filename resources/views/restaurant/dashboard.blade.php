@php
    $title = 'Restaurant Dashboard';
    $panelName = 'Restaurant Panel';
    $heading = 'Menu Items';
    $subheading = 'View imported items, addons, and variations';
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
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">#</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Image</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Name</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Price</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Variations/Addons</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Created At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($items as $index => $item)
                        <tr class="align-top">
                            <td class="px-4 py-3">{{ $items->firstItem() + $index }}</td>
                            <td class="px-4 py-3">
                                @if($item->image)
                                    <img src="{{ $item->image }}" alt="{{ $item->name }}" class="h-12 w-12 rounded-lg object-cover">
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-medium">{{ $item->name }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $displayPrice = $item->price;

                                    if ($item->price == 0 && $item->variations->count() > 0) {
                                        $displayPrice = $item->variations->min('web_price');
                                    }
                                @endphp

                                Rs {{ $displayPrice ?? 0 }}
                            </td>
                            <td class="px-4 py-3">
                                @if($item->is_available)
                                    <span class="inline-flex rounded-full bg-success-100 px-2.5 py-1 text-xs font-semibold text-success-700">Active</span>
                                @else
                                    <span class="inline-flex rounded-full bg-error-100 px-2.5 py-1 text-xs font-semibold text-error-700">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="space-y-2">
                                    <details class="rounded-lg border border-gray-200 p-2">
                                        <summary class="cursor-pointer text-xs font-semibold text-brand-600">Variations</summary>
                                        @if($item->variations->count() > 0)
                                            <div class="mt-2 overflow-x-auto">
                                                <table class="min-w-full text-xs">
                                                    <thead>
                                                        <tr class="text-gray-500">
                                                            <th class="px-2 py-1 text-left">Name</th>
                                                            <th class="px-2 py-1 text-left">POS</th>
                                                            <th class="px-2 py-1 text-left">Web</th>
                                                            <th class="px-2 py-1 text-left">Mobile</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($item->variations as $var)
                                                            <tr class="border-t border-gray-100">
                                                                <td class="px-2 py-1">{{ $var->variation->variation_name ?? '-' }}</td>
                                                                <td class="px-2 py-1">Rs {{ $var->pos_price }}</td>
                                                                <td class="px-2 py-1">Rs {{ $var->web_price }}</td>
                                                                <td class="px-2 py-1">Rs {{ $var->mobile_price }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <p class="mt-2 text-xs text-gray-500">No variations available.</p>
                                        @endif
                                    </details>

                                    <details class="rounded-lg border border-gray-200 p-2">
                                        <summary class="cursor-pointer text-xs font-semibold text-brand-600">Addons</summary>
                                        @if($item->addons->count() > 0)
                                            <div class="mt-2 overflow-x-auto">
                                                <table class="min-w-full text-xs">
                                                    <thead>
                                                        <tr class="text-gray-500">
                                                            <th class="px-2 py-1 text-left">Addon Name</th>
                                                            <th class="px-2 py-1 text-left">POS</th>
                                                            <th class="px-2 py-1 text-left">Web</th>
                                                            <th class="px-2 py-1 text-left">Mobile</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($item->addons as $addon)
                                                            <tr class="border-t border-gray-100">
                                                                <td class="px-2 py-1">{{ $addon->addonItem->name ?? '-' }}</td>
                                                                <td class="px-2 py-1">Rs {{ $addon->pos_price }}</td>
                                                                <td class="px-2 py-1">Rs {{ $addon->web_price }}</td>
                                                                <td class="px-2 py-1">Rs {{ $addon->mobile_price }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <p class="mt-2 text-xs text-gray-500">No addons available.</p>
                                        @endif
                                    </details>
                                </div>
                            </td>
                            <td class="px-4 py-3">{{ $item->created_at->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-gray-500">No Items Found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    

    <div class="mt-4">
        {{ $items->links() }}
    </div>
@endsection


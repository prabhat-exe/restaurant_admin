@php
    $title = 'Restaurant Settings';
    $panelName = 'Restaurant Panel';
    $heading = 'Setting';
    $subheading = 'Edit restaurant profile and menu defaults';
    $logoutRoute = 'restaurant.logout';
    $navLinks = [
        ['label' => 'Items', 'route' => 'restaurant.dashboard'],
        ['label' => 'Delivery', 'route' => 'restaurant.delivery'],
        ['label' => 'Setting', 'route' => 'restaurant.settings'],
        ['label' => 'Orders', 'route' => 'restaurant.orders'],
        ['label' => 'Menu Import', 'route' => 'menu.import.form'],
    ];
@endphp

@extends('layouts.panel')

@section('content')
    <div class="max-w-4xl">
        @if(session('success'))
            <div class="mb-4 rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700">
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="mb-4 rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-sm sm:p-6 dark:border-gray-800 dark:bg-gray-900">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Restaurant Details</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Keep your store name, currency, preparation time, and public descriptions up to date.
                </p>
            </div>

            <form method="POST" action="{{ route('restaurant.settings.update') }}" class="space-y-5">
                @csrf

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="restaurant_name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Restaurant name</label>
                        <input
                            id="restaurant_name"
                            type="text"
                            name="name"
                            value="{{ old('name', $restaurant->name) }}"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:text-gray-100"
                            required
                        >
                    </div>

                    <div>
                        <label for="country_currency" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Currency</label>
                        <input
                            id="country_currency"
                            type="text"
                            name="country_currency"
                            value="{{ old('country_currency', $restaurant->country_currency) }}"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:text-gray-100"
                            placeholder="₹ or INR"
                        >
                    </div>

                    <div>
                        <label for="cook_time" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cook time (minutes)</label>
                        <input
                            id="cook_time"
                            type="number"
                            name="cook_time"
                            min="0"
                            max="1440"
                            value="{{ old('cook_time', $restaurant->cook_time) }}"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:text-gray-100"
                            placeholder="30"
                        >
                    </div>
                </div>

                <div>
                    <label for="short_description" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Short description</label>
                    <textarea
                        id="short_description"
                        name="short_description"
                        rows="3"
                        class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:text-gray-100 dark:placeholder-gray-500"
                        placeholder="A brief line customers will see first"
                    >{{ old('short_description', $restaurant->short_description) }}</textarea>
                </div>

                <div>
                    <label for="description" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                    <textarea
                        id="description"
                        name="description"
                        rows="6"
                        class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:text-gray-100 dark:placeholder-gray-500"
                        placeholder="Full restaurant description"
                    >{{ old('description', $restaurant->description) }}</textarea>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('restaurant.dashboard') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-transparent dark:text-gray-200 dark:hover:bg-white/5">
                        Cancel
                    </a>
                    <button
                        type="submit"
                        class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-brand-600"
                    >
                        Save Setting
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

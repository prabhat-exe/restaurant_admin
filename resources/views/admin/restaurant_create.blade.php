@php
    $title = 'Add New Restaurant';
    $panelName = 'Admin Panel';
    $heading = 'Add New Restaurant';
    $subheading = 'Create credentials and profile for a restaurant';
    $logoutRoute = 'admin.logout';
    $navLinks = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard'],
        ['label' => 'Add Restaurant', 'route' => 'admin.restaurant.create'],
    ];
@endphp

@extends('layouts.panel')

@section('content')
    <style>
        .parsley-errors-list {
            color: #f04438;
            font-size: 0.875rem;
            margin-top: 0.375rem;
            list-style: none;
            padding-left: 0;
        }
    </style>

    <div class="max-w-4xl rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm">
        <form method="POST" action="/admin/restaurant/store" id="restaurantForm" data-parsley-validate>
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden" name="name" required data-parsley-required-message="Restaurant name is required">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden" name="email" required data-parsley-type="email" data-parsley-type-message="Enter valid email address">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Phone</label>
                    <input type="text" class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden" name="phone" required data-parsley-pattern="^[0-9]{10,15}$" data-parsley-pattern-message="Enter valid phone (10-15 digits)">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden" id="password" name="password" required data-parsley-minlength="8" data-parsley-minlength-message="Password must be at least 8 characters">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Confirm Password</label>
                    <input type="password" class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden" name="password_confirmation" required data-parsley-equalto="#password" data-parsley-equalto-message="Passwords do not match">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Address</label>
                    <input type="text" class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden" name="address" required>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">City</label>
                    <input type="text" class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden" name="city" required>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">State</label>
                    <input type="text" class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden" name="state" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Pincode</label>
                    <input type="text" class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden" name="pincode" required data-parsley-pattern="^[0-9]{4,10}$" data-parsley-pattern-message="Enter valid pincode">
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-2">
                <a href="/admin/dashboard" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">Cancel</a>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Register Restaurant</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/parsley.js/2.9.2/parsley.min.js"></script>
    <script>
        $(function() {
            $('#restaurantForm').parsley();
        });
    </script>
@endpush

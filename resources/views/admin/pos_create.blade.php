@php
    $title = 'Add POS';
    $panelName = 'Admin Panel';
    $heading = 'Add New POS';
    $subheading = 'Register POS credentials and link it with a restaurant';
    $logoutRoute = 'admin.logout';
    $navLinks = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard'],
        ['label' => 'Add Restaurant', 'route' => 'admin.restaurant.create'],
        ['label' => 'POS List', 'route' => 'admin.pos.index'],
        ['label' => 'Add POS', 'route' => 'admin.pos.create'],
    ];
@endphp

@extends('layouts.panel')

@section('content')
    <div class="max-w-4xl rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm">
        <form method="POST" action="{{ route('admin.pos.store') }}" class="grid gap-4 sm:grid-cols-2">
            @csrf

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">POS Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">POS Menu URL</label>
                <input type="url" name="menu_url" value="{{ old('menu_url') }}" required placeholder="https://pos.example.com/api/menu" class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Client ID</label>
                <input type="text" name="client_id" value="{{ old('client_id') }}" required class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Public Key</label>
                <input type="text" name="public_key" value="{{ old('public_key') }}" required class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden">
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Secret Key</label>
                <input type="text" name="secret_key" value="{{ old('secret_key') }}" required class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">POS Login Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">POS Login Password</label>
                <input type="password" name="password" required minlength="6" class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden">
            </div>

            <div class="sm:col-span-2 flex items-center justify-between pt-2">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-gray-300 text-brand-600">
                    Active
                </label>

                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">
                    Save POS
                </button>
            </div>
        </form>
    </div>
@endsection

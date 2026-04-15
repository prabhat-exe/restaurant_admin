@php
    $title = 'POS List';
    $panelName = 'Admin Panel';
    $heading = 'POS List';
    $subheading = 'Manage POS credentials and sync menus';
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
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">POS</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Email</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Menu URL</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Last Sync</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($posSystems as $pos)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $pos->name }}</td>
                            <td class="px-4 py-3">{{ $pos->email }}</td>
                            <td class="px-4 py-3 max-w-[260px] truncate">{{ $pos->menu_url }}</td>
                            <td class="px-4 py-3">
                                @if($pos->is_active)
                                    <span class="inline-flex rounded-full bg-success-100 px-2.5 py-1 text-xs font-semibold text-success-700">Active</span>
                                @else
                                    <span class="inline-flex rounded-full bg-error-100 px-2.5 py-1 text-xs font-semibold text-error-700">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($pos->last_synced_at)
                                    {{ $pos->last_synced_at->format('Y-m-d H:i:s') }} ({{ $pos->last_sync_status ?? 'unknown' }})
                                @else
                                    Not synced
                                @endif
                                @if($pos->last_sync_error)
                                    <div class="mt-1 text-xs text-error-600">{{ $pos->last_sync_error }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('admin.pos.sync', $pos->id) }}">
                                    @csrf
                                    <button type="submit" class="rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-600">
                                        Sync Menu
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="6" class="bg-gray-50 px-4 py-3">
                                <form method="POST" action="{{ route('admin.pos.update', $pos->id) }}" class="grid gap-3 sm:grid-cols-3">
                                    @csrf
                                    @method('PUT')
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-gray-700">POS Name</label>
                                        <input type="text" name="name" value="{{ $pos->name }}" required placeholder="POS name" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-gray-700">POS Login Email</label>
                                        <input type="email" name="email" value="{{ $pos->email }}" required class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" placeholder="POS login email">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-gray-700">Menu URL</label>
                                        <input type="url" name="menu_url" value="{{ $pos->menu_url }}" required placeholder="POS menu URL" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-gray-700">Client ID</label>
                                        <input type="text" name="client_id" value="{{ $pos->client_id }}" required placeholder="Client ID" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-gray-700">Public Key</label>
                                        <input type="text" name="public_key" value="{{ $pos->public_key }}" required placeholder="Public key" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-gray-700">Secret Key</label>
                                        <input type="text" name="secret_key" value="{{ $pos->secret_key }}" required placeholder="Secret key" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-gray-700">New Password</label>
                                        <input type="password" name="password" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" placeholder="Leave blank to keep current">
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <label class="inline-flex items-center gap-2 text-xs text-gray-700">
                                            <input type="checkbox" name="is_active" value="1" @checked($pos->is_active) class="h-4 w-4 rounded border-gray-300 text-brand-600">
                                            Active
                                        </label>
                                        <button type="submit" class="rounded-lg border border-brand-200 bg-white px-3 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">
                                            Update POS
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">No POS configured yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $posSystems->links() }}
    </div>
@endsection

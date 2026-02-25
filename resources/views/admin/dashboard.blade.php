@php
    $title = 'Admin Dashboard';
    $panelName = 'Admin Panel';
    $heading = 'Registered Restaurants';
    $subheading = 'Manage all onboarded restaurants';
    $logoutRoute = 'admin.logout';
    $navLinks = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard'],
        ['label' => 'Add Restaurant', 'route' => 'admin.restaurant.create'],
    ];
@endphp

@extends('layouts.panel')

@section('content')
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Name</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Email</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Phone</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Address</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Edit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($restaurants as $restaurant)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $restaurant->name }}</td>
                            <td class="px-4 py-3">{{ $restaurant->email }}</td>
                            <td class="px-4 py-3">{{ $restaurant->phone }}</td>
                            <td class="px-4 py-3">{{ $restaurant->address }}</td>
                            <td class="px-4 py-3">
                                @if($restaurant->is_active)
                                    <span class="inline-flex rounded-full bg-success-100 px-2.5 py-1 text-xs font-semibold text-success-700">Active</span>
                                @else
                                    <span class="inline-flex rounded-full bg-error-100 px-2.5 py-1 text-xs font-semibold text-error-700">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <button
                                    class="edit-btn rounded-lg border border-brand-200 bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-100"
                                    data-id="{{ $restaurant->id }}"
                                    data-name="{{ e($restaurant->name) }}"
                                    data-email="{{ e($restaurant->email) }}"
                                    data-phone="{{ e($restaurant->phone) }}"
                                    data-address="{{ e($restaurant->address) }}"
                                    data-status="{{ $restaurant->is_active }}"
                                >
                                    Edit
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div id="editRestaurantModal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-lg rounded-xl border border-gray-200 bg-white p-5 shadow-theme-lg">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Edit Restaurant</h3>
                <button id="closeModal" class="rounded-md p-1 text-gray-500 hover:bg-gray-100 hover:text-gray-700">✕</button>
            </div>

            <form id="editRestaurantForm" class="space-y-4">
                <input type="hidden" id="edit_id" name="id">
                <div>
                    <label for="edit_name" class="mb-1.5 block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden" id="edit_name" name="name" required>
                </div>
                <div>
                    <label for="edit_email" class="mb-1.5 block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden" id="edit_email" name="email" required>
                </div>
                <div>
                    <label for="edit_phone" class="mb-1.5 block text-sm font-medium text-gray-700">Phone</label>
                    <input type="text" class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden" id="edit_phone" name="phone" required>
                </div>
                <div>
                    <label for="edit_address" class="mb-1.5 block text-sm font-medium text-gray-700">Address</label>
                    <input type="text" class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden" id="edit_address" name="address" required>
                </div>
                <div>
                    <label for="edit_status" class="mb-1.5 block text-sm font-medium text-gray-700">Status</label>
                    <select class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden" id="edit_status" name="is_active">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" id="cancelModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">Cancel</button>
                    <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(function() {
            const $modal = $('#editRestaurantModal');

            function closeModal() {
                $modal.addClass('hidden').removeClass('flex');
            }

            function openModal() {
                $modal.removeClass('hidden').addClass('flex');
            }

            $('.edit-btn').on('click', function() {
                $('#edit_id').val($(this).data('id'));
                $('#edit_name').val($(this).data('name'));
                $('#edit_email').val($(this).data('email'));
                $('#edit_phone').val($(this).data('phone'));
                $('#edit_address').val($(this).data('address'));
                $('#edit_status').val($(this).data('status'));
                openModal();
            });

            $('#closeModal, #cancelModal').on('click', closeModal);

            $modal.on('click', function(e) {
                if (e.target === this) {
                    closeModal();
                }
            });

            $('#editRestaurantForm').on('submit', function(e) {
                e.preventDefault();
                const id = $('#edit_id').val();
                const data = $(this).serialize();

                $.ajax({
                    url: '/admin/restaurant/update/' + id,
                    method: 'POST',
                    data: data + '&_token={{ csrf_token() }}',
                    success: function() {
                        location.reload();
                    },
                    error: function() {
                        alert('Update failed. Please check your input.');
                    }
                });
            });
        });
    </script>
@endpush

@php
    $isEdit = !empty($item);
    $title = $isEdit ? 'Edit Item' : 'Add Item';
    $panelName = 'Restaurant Panel';
    $heading = $isEdit ? 'Edit Menu Item' : 'Add Menu Item';
    $subheading = 'Manage item details, variations, and addons';
    $logoutRoute = 'restaurant.logout';
    $navLinks = [
        ['label' => 'Items', 'route' => 'restaurant.dashboard'],
        ['label' => 'Orders', 'route' => 'restaurant.orders'],
        ['label' => 'Menu Import', 'route' => 'menu.import.form'],
    ];
@endphp

@extends('layouts.panel')

@section('content')
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
        <form method="POST" action="{{ $isEdit ? route('restaurant.items.update', $item) : route('restaurant.items.store') }}">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Item Name</label>
                    <input type="text" name="name" required value="{{ old('name', $item->name ?? '') }}"
                        class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:text-gray-100">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
                    <select name="category_id" required
                        class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:text-gray-100">
                        <option value="">Select category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) old('category_id', $item->category_id ?? 0) === (int) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Base Price</label>
                    <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $item->price ?? 0) }}"
                        class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:text-gray-100">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Image URL</label>
                    <input type="text" name="image" value="{{ old('image', $item->image ?? '') }}"
                        class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:text-gray-100">
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                    <textarea name="description" rows="3"
                        class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:text-gray-100">{{ old('description', $item->description ?? '') }}</textarea>
                </div>
            </div>

            <label class="mt-4 inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="hidden" name="is_available" value="0">
                <input type="checkbox" name="is_available" value="1" @checked((bool) old('is_available', $item->is_available ?? true))>
                Item Active
            </label>

            <div class="mt-6 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Variations</h3>
                    <button type="button" id="addVariationRow"
                        class="rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-600">
                        Add Variation
                    </button>
                </div>
                <div id="variationRows" class="space-y-2"></div>
            </div>

            <div class="mt-6 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Addons</h3>
                    <button type="button" id="addAddonRow"
                        class="rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-600">
                        Add Addon
                    </button>
                </div>
                <div id="addonRows" class="space-y-2"></div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button
                    class="inline-flex items-center rounded-lg bg-success-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-success-700 transition">
                    {{ $isEdit ? 'Update Item' : 'Create Item' }}
                </button>
                <a href="{{ route('restaurant.dashboard') }}"
                    class="inline-flex items-center rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    @php
        $variationPayload = old('variations');
        if ($variationPayload === null) {
            $variationPayload = $existingVariations->map(function ($v) {
                return [
                    'variation_name' => $v->variation->variation_name ?? '',
                    'pos_price' => $v->pos_price ?? 0,
                    'web_price' => $v->web_price ?? 0,
                    'mobile_price' => $v->mobile_price ?? 0,
                ];
            })->values()->all();
        }

        $addonPayload = old('addons');
        if ($addonPayload === null) {
            $addonPayload = $existingAddons->map(function ($a) {
                return [
                    'addon_item_id' => $a->addon_item_id,
                    'addon_name' => '',
                    'addon_price' => $a->addonItem->price ?? 0,
                    'pos_price' => $a->pos_price ?? 0,
                    'web_price' => $a->web_price ?? 0,
                    'mobile_price' => $a->mobile_price ?? 0,
                ];
            })->values()->all();
        }
    @endphp
    <script>
        (function () {
            const variationRows = document.getElementById('variationRows');
            const addonRows = document.getElementById('addonRows');
            const addVariationBtn = document.getElementById('addVariationRow');
            const addAddonBtn = document.getElementById('addAddonRow');
            const addonItems = @json($addonItems);
            const initialVariations = @json($variationPayload);
            const initialAddons = @json($addonPayload);
            let variationIndex = 0;
            let addonIndex = 0;

            function numValue(value) {
                return value === null || value === undefined || value === '' ? 0 : value;
            }

            function renderVariationRow(data = {}) {
                const row = document.createElement('div');
                row.className = 'grid gap-2 md:grid-cols-12';
                row.innerHTML = `
                    <input type="text" name="variations[${variationIndex}][variation_name]" value="${data.variation_name ?? ''}" placeholder="Name (e.g. Half)"
                        class="md:col-span-3 rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm dark:border-gray-700">
                    <input type="number" step="0.01" min="0" name="variations[${variationIndex}][pos_price]" value="${numValue(data.pos_price)}" placeholder="POS"
                        class="md:col-span-2 rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm dark:border-gray-700">
                    <input type="number" step="0.01" min="0" name="variations[${variationIndex}][web_price]" value="${numValue(data.web_price)}" placeholder="Web"
                        class="md:col-span-2 rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm dark:border-gray-700">
                    <input type="number" step="0.01" min="0" name="variations[${variationIndex}][mobile_price]" value="${numValue(data.mobile_price)}" placeholder="Mobile"
                        class="md:col-span-2 rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm dark:border-gray-700">
                    <button type="button" class="md:col-span-3 rounded-lg bg-error-500 px-3 py-2 text-xs font-medium text-white">Remove</button>
                `;
                row.querySelector('button').addEventListener('click', () => row.remove());
                variationRows.appendChild(row);
                variationIndex++;
            }

            function buildAddonOptions(selected) {
                const options = [`<option value="">Create New Addon</option>`];
                addonItems.forEach((addon) => {
                    const isSelected = Number(selected) === Number(addon.id) ? 'selected' : '';
                    options.push(`<option value="${addon.id}" ${isSelected}>${addon.name}</option>`);
                });
                return options.join('');
            }

            function renderAddonRow(data = {}) {
                const row = document.createElement('div');
                row.className = 'grid gap-2 md:grid-cols-12';
                row.innerHTML = `
                    <select name="addons[${addonIndex}][addon_item_id]" class="md:col-span-3 rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm dark:border-gray-700">
                        ${buildAddonOptions(data.addon_item_id)}
                    </select>
                    <input type="text" name="addons[${addonIndex}][addon_name]" value="${data.addon_name ?? ''}" placeholder="New addon name"
                        class="md:col-span-2 rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm dark:border-gray-700">
                    <input type="number" step="0.01" min="0" name="addons[${addonIndex}][addon_price]" value="${numValue(data.addon_price)}" placeholder="New addon base"
                        class="md:col-span-2 rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm dark:border-gray-700">
                    <input type="number" step="0.01" min="0" name="addons[${addonIndex}][pos_price]" value="${numValue(data.pos_price)}" placeholder="POS"
                        class="md:col-span-1 rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm dark:border-gray-700">
                    <input type="number" step="0.01" min="0" name="addons[${addonIndex}][web_price]" value="${numValue(data.web_price)}" placeholder="Web"
                        class="md:col-span-1 rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm dark:border-gray-700">
                    <input type="number" step="0.01" min="0" name="addons[${addonIndex}][mobile_price]" value="${numValue(data.mobile_price)}" placeholder="Mobile"
                        class="md:col-span-1 rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm dark:border-gray-700">
                    <button type="button" class="md:col-span-2 rounded-lg bg-error-500 px-3 py-2 text-xs font-medium text-white">Remove</button>
                `;
                row.querySelector('button').addEventListener('click', () => row.remove());
                addonRows.appendChild(row);
                addonIndex++;
            }

            addVariationBtn.addEventListener('click', () => renderVariationRow());
            addAddonBtn.addEventListener('click', () => renderAddonRow());

            if (initialVariations.length > 0) {
                initialVariations.forEach((row) => renderVariationRow(row));
            } else {
                renderVariationRow();
            }

            if (initialAddons.length > 0) {
                initialAddons.forEach((row) => renderAddonRow(row));
            }
        })();
    </script>
@endpush

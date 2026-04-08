@php
    $title = 'Delivery Settings';
    $panelName = 'Restaurant Panel';
    $heading = 'Delivery Settings';
    $subheading = 'Manage delivery location and service radius';
    $logoutRoute = 'restaurant.logout';
    $navLinks = [
        ['label' => 'Items', 'route' => 'restaurant.dashboard'],
        ['label' => 'Delivery', 'route' => 'restaurant.delivery'],
        ['label' => 'Orders', 'route' => 'restaurant.orders'],
        ['label' => 'Menu Import', 'route' => 'menu.import.form'],
    ];
@endphp

@extends('layouts.panel')

@section('content')
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

    <div class="grid gap-6 xl:grid-cols-[380px_1fr]">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Delivery Area</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Set your restaurant location and delivery radius. Customers outside this area will not be able to place delivery orders.
                    </p>
                </div>
                <span class="inline-flex rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">
                    Radius Control
                </span>
            </div>

            <form method="POST" action="{{ route('restaurant.delivery.settings') }}" class="mt-5 space-y-4">
                @csrf

                <div>
                    <label for="restaurant_address" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Restaurant address</label>
                    <textarea
                        id="restaurant_address"
                        name="address"
                        rows="3"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none transition focus:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        placeholder="Store address"
                    >{{ old('address', $restaurant->address) }}</textarea>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="delivery_latitude" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Latitude</label>
                        <input
                            id="delivery_latitude"
                            name="latitude"
                            type="number"
                            step="any"
                            value="{{ old('latitude', $restaurant->latitude) }}"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none transition focus:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            placeholder="28.6139000"
                            required
                        >
                    </div>
                    <div>
                        <label for="delivery_longitude" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Longitude</label>
                        <input
                            id="delivery_longitude"
                            name="longitude"
                            type="number"
                            step="any"
                            value="{{ old('longitude', $restaurant->longitude) }}"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none transition focus:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            placeholder="77.2090000"
                            required
                        >
                    </div>
                </div>

                <div>
                    <label for="delivery_radius_km" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Delivery radius (km)</label>
                    <input
                        id="delivery_radius_km"
                        name="delivery_radius_km"
                        type="number"
                        step="0.1"
                        min="0.1"
                        value="{{ old('delivery_radius_km', $restaurant->delivery_radius_km) }}"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2 outline-none transition focus:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        placeholder="5"
                        required
                    >
                </div>

                <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    Click on the map to move the restaurant marker. The red circle updates live from the radius field.
                </div>

                <button
                    type="submit"
                    class="inline-flex items-center rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-600"
                >
                    Save Delivery Area
                </button>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Delivery Map Preview</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Use this like a service-area editor for your restaurant.</p>
            </div>
            <div
                class="relative w-full overflow-hidden bg-gray-100 dark:bg-gray-800"
                style="height: 420px; min-height: 420px;"
            >
                <div
                    id="delivery-area-map"
                    class="absolute inset-0 z-10"
                    style="width: 100%; height: 100%; min-height: 420px;"
                ></div>
                <div
                    id="delivery-area-fallback"
                    class="absolute inset-0 z-0"
                    style="width: 100%; height: 100%; min-height: 420px;"
                >
                    <svg
                        id="delivery-area-fallback-svg"
                        viewBox="0 0 800 420"
                        class="h-full w-full"
                        style="display: block; width: 100%; height: 100%; min-height: 420px;"
                    >
                        <defs>
                            <pattern id="deliveryGridPattern" width="40" height="40" patternUnits="userSpaceOnUse">
                                <path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(148,163,184,0.25)" stroke-width="1"></path>
                            </pattern>
                        </defs>
                        <rect x="0" y="0" width="800" height="420" fill="url(#deliveryGridPattern)"></rect>
                        <rect x="0" y="0" width="800" height="420" fill="rgba(15,23,42,0.06)"></rect>
                        <circle id="delivery-area-radius-circle" cx="400" cy="210" r="110" fill="rgba(248,113,113,0.18)" stroke="#dc2626" stroke-width="3"></circle>
                        <circle id="delivery-area-store-dot" cx="400" cy="210" r="12" fill="#dc2626"></circle>
                        <circle id="delivery-area-store-pulse" cx="400" cy="210" r="22" fill="rgba(220,38,38,0.15)"></circle>
                        <text x="400" y="60" text-anchor="middle" fill="#0f172a" font-size="20" font-weight="600">
                            Delivery radius preview
                        </text>
                        <text id="delivery-area-fallback-label" x="400" y="360" text-anchor="middle" fill="#334155" font-size="18">
                            Set latitude, longitude, and radius to preview your service area
                        </text>
                    </svg>
                </div>
                <div id="delivery-area-status" class="absolute bottom-4 left-4 right-4 z-20 rounded-xl border border-white/60 bg-white/90 px-4 py-3 text-sm text-slate-700 shadow-lg backdrop-blur dark:border-slate-700 dark:bg-slate-900/90 dark:text-slate-200">
                    Loading map preview...
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    />
    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    ></script>
    <script>
        (function () {
            const mapElement = document.getElementById('delivery-area-map');
            const fallbackCircle = document.getElementById('delivery-area-radius-circle');
            const fallbackDot = document.getElementById('delivery-area-store-dot');
            const fallbackPulse = document.getElementById('delivery-area-store-pulse');
            const fallbackLabel = document.getElementById('delivery-area-fallback-label');
            const statusElement = document.getElementById('delivery-area-status');
            const latInput = document.getElementById('delivery_latitude');
            const lngInput = document.getElementById('delivery_longitude');
            const radiusInput = document.getElementById('delivery_radius_km');

            if (!mapElement || !latInput || !lngInput || !radiusInput) {
                return;
            }

            const fallbackLat = parseFloat(latInput.value || '28.6139');
            const fallbackLng = parseFloat(lngInput.value || '77.2090');
            const fallbackRadius = parseFloat(radiusInput.value || '5');

            let map = null;
            let marker = null;
            let circle = null;

            const updateFallbackPreview = function () {
                const lat = parseFloat(latInput.value || fallbackLat);
                const lng = parseFloat(lngInput.value || fallbackLng);
                const radius = parseFloat(radiusInput.value || '0');

                if (Number.isNaN(lat) || Number.isNaN(lng)) {
                    if (statusElement) {
                        statusElement.textContent = 'Enter a valid latitude and longitude to preview the delivery area.';
                    }
                    return;
                }

                const normalizedRadius = Math.max(radius, 0);
                const svgRadius = Math.max(45, Math.min(150, normalizedRadius * 18));

                if (fallbackCircle) {
                    fallbackCircle.setAttribute('cx', '400');
                    fallbackCircle.setAttribute('cy', '210');
                    fallbackCircle.setAttribute('r', String(svgRadius));
                }

                if (fallbackDot) {
                    fallbackDot.setAttribute('cx', '400');
                    fallbackDot.setAttribute('cy', '210');
                }

                if (fallbackPulse) {
                    fallbackPulse.setAttribute('cx', '400');
                    fallbackPulse.setAttribute('cy', '210');
                }

                if (fallbackLabel) {
                    fallbackLabel.textContent = 'Lat: ' + lat.toFixed(6) + ' | Lng: ' + lng.toFixed(6) + ' | Radius: ' + normalizedRadius.toFixed(1) + ' km';
                }

                if (statusElement) {
                    statusElement.textContent = 'Leaflet map is loading. If it fails, this local delivery preview will stay visible.';
                }
            };

            const syncMap = function () {
                const lat = parseFloat(latInput.value || fallbackLat);
                const lng = parseFloat(lngInput.value || fallbackLng);
                const radius = parseFloat(radiusInput.value || '0');

                updateFallbackPreview();

                if (!map || Number.isNaN(lat) || Number.isNaN(lng)) {
                    return;
                }

                const latLng = [lat, lng];
                if (marker) {
                    marker.setLatLng(latLng);
                }
                if (circle) {
                    circle.setLatLng(latLng);
                    circle.setRadius(Math.max(radius, 0) * 1000);
                }
                map.panTo(latLng);
            };

            updateFallbackPreview();

            latInput.addEventListener('input', syncMap);
            lngInput.addEventListener('input', syncMap);
            radiusInput.addEventListener('input', syncMap);

            if (typeof window.L === 'undefined') {
                if (statusElement) {
                    statusElement.textContent = 'Leaflet failed to load. Showing local delivery preview only.';
                }
                return;
            }

            map = window.L.map(mapElement).setView([fallbackLat, fallbackLng], 13);

            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map);

            marker = window.L.marker([fallbackLat, fallbackLng], {
                draggable: true,
            }).addTo(map);

            circle = window.L.circle([fallbackLat, fallbackLng], {
                radius: Math.max(fallbackRadius, 0) * 1000,
                color: '#dc2626',
                fillColor: '#f87171',
                fillOpacity: 0.2,
            }).addTo(map);

            marker.on('dragend', function (event) {
                const latLng = event.target.getLatLng();
                latInput.value = latLng.lat.toFixed(7);
                lngInput.value = latLng.lng.toFixed(7);
                syncMap();
            });

            map.on('click', function (event) {
                latInput.value = event.latlng.lat.toFixed(7);
                lngInput.value = event.latlng.lng.toFixed(7);
                syncMap();
            });

            map.invalidateSize();

            if (statusElement) {
                statusElement.textContent = 'OpenStreetMap loaded. Click on the map or drag the marker to update the delivery area.';
            }
        })();
    </script>
@endpush

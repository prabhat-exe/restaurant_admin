@php
    $title = 'Import Menu';
    $panelName = 'Restaurant Panel';
    $heading = 'Import Menu';
    $subheading = 'Upload JSON payload to create menu records';
    $logoutRoute = 'restaurant.logout';
    $navLinks = [
        ['label' => 'Items', 'route' => 'restaurant.dashboard'],
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
            @if(!empty($hasMenu) && $hasMenu)
                <div class="rounded-lg border border-warning-200 bg-warning-50 p-4 text-sm text-warning-700">
                    Menu already exists for this restaurant. Delete it permanently to import a fresh menu.
                </div>

                <form method="POST" action="{{ route('menu.destroy') }}" class="mt-4"
                    onsubmit="return confirm('This will permanently delete your full menu. Continue?');">
                    @csrf
                    @method('DELETE')

                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Type <span class="font-semibold">DELETE</span> to confirm
                    </label>
                    <input
                        type="text"
                        name="confirm_text"
                        placeholder="DELETE"
                        class="mb-4 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:text-gray-200 dark:placeholder-gray-500 dark:border-gray-700"
                    >

                    <button
                        class="inline-flex items-center rounded-lg bg-error-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-error-600 transition">
                        Delete Entire Menu
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('menu.import') }}" enctype="multipart/form-data">
                    @csrf

                    <!-- JSON OPTION -->
                    <div class="mb-6">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Paste JSON Here
                        </label>

                        <textarea
                            name="json_data"
                            rows="12"
                            class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:text-gray-200 dark:placeholder-gray-500 dark:border-gray-700"
                            placeholder="Paste menu JSON payload"
                        ></textarea>
                    </div>

                    <!-- Divider -->
                    <div class="flex items-center gap-4 mb-6">
                        <div class="flex-1 border-t border-gray-300 dark:border-gray-700"></div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">OR</span>
                        <div class="flex-1 border-t border-gray-300 dark:border-gray-700"></div>
                    </div>

                    <!-- EXCEL OPTION -->
                    <div class="mb-6">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Upload Excel File (.xlsx)
                        </label>
                        <a href="{{ route('menu.sample.download') }}"
                            class="mb-4 inline-flex items-center gap-2 rounded-lg border border-brand-500 px-4 py-2 text-sm font-medium text-brand-600 hover:bg-brand-50 dark:hover:bg-brand-500/10">
                                ⬇ Download Sample Excel
                        </a>
                        <input
                            type="file"
                            name="excel_file"
                            accept=".xlsx,.xls"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-brand-500 file:px-4 file:py-2 file:text-white hover:file:bg-brand-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                        >
                    </div>

                    <button
                        class="inline-flex items-center rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600 transition">
                        Import Menu
                    </button>
                </form>
            @endif

        </div>
    </div>
@endsection

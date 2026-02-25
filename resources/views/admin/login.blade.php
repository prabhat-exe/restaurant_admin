<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login</title>
    @include('layouts.partials.theme-init')
    @include('layouts.partials.assets')
    <style>
        .parsley-errors-list {
            color: #f04438;
            font-size: 0.875rem;
            margin-top: 0.375rem;
            list-style: none;
            padding-left: 0;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <button id="themeToggle" type="button" class="fixed top-4 right-4 z-50 inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700" aria-label="Toggle theme">
        <span class="dark:hidden">☀</span>
        <span class="hidden dark:inline">☾</span>
    </button>

    <div class="relative z-1 bg-white p-6 sm:p-0 dark:bg-gray-900">
        <div class="relative flex min-h-screen w-full flex-col justify-center sm:p-0 lg:flex-row dark:bg-gray-900">
            <div class="flex w-full flex-1 flex-col lg:w-1/2">
                <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center py-10">
                    <div class="mb-8">
                        <div class="relative hidden w-full items-center justify-center bg-brand-950 lg:flex dark:bg-gray-800 !W-100">
                            <div class="mx-auto max-w-sm px-8 text-center w-100 py-4">
                                <h2 class="text-2xl font-semibold text-white">Admin Panel</h2>
                            </div>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Enter admin credentials to continue.</p>
                    </div>

                    @if ($errors->any())
                        <div class="mb-5 rounded-lg border border-error-200 bg-error-50 p-3 text-sm text-error-700">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.login.submit') }}" id="loginForm" data-parsley-validate>
                        @csrf
                        <div class="space-y-5">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Email<span class="text-error-500">*</span></label>
                                <input
                                    type="email"
                                    name="email"
                                    required
                                    data-parsley-type="email"
                                    data-parsley-trigger="keyup"
                                    data-parsley-type-message="Enter valid email address"
                                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:text-white"
                                    placeholder="admin@example.com"
                                >
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Password<span class="text-error-500">*</span></label>
                                <input
                                    type="password"
                                    name="password"
                                    required
                                    data-parsley-minlength="6"
                                    data-parsley-trigger="keyup"
                                    data-parsley-minlength-message="Password must be at least 6 characters"
                                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:text-white"
                                    placeholder="Enter password"
                                >
                            </div>
                            <button class="flex h-11 w-full items-center justify-center rounded-lg bg-brand-500 px-4 text-sm font-medium text-white transition hover:bg-brand-600">
                                Login
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/parsleyjs@2.9.2/dist/parsley.min.js"></script>
    <script>
        $('#loginForm').parsley();
        document.getElementById('themeToggle').addEventListener('click', function () {
            const root = document.documentElement;
            const next = root.classList.contains('dark') ? 'light' : 'dark';
            root.classList.toggle('dark', next === 'dark');
            localStorage.setItem('theme', next);
        });
    </script>
</body>
</html>

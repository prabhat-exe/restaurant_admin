<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found</title>
    @include('layouts.partials.theme-init')
    @include('layouts.partials.assets')
</head>
<body class="flex min-h-screen items-center justify-center bg-gray-50 px-4 dark:bg-gray-900">
    <button id="themeToggle" type="button" class="fixed top-4 right-4 z-50 inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700" aria-label="Toggle theme">
        <span class="dark:hidden">☀</span>
        <span class="hidden dark:inline">☾</span>
    </button>

    <div class="w-full max-w-xl rounded-xl border border-gray-200 bg-white p-8 text-center shadow-theme-md dark:border-gray-700 dark:bg-gray-800">
        <p class="mb-2 text-sm font-semibold text-brand-600">404 Error</p>
        <h1 class="mb-3 text-3xl font-semibold text-gray-900 dark:text-white">Page Not Found</h1>
        <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">Sorry, the page you are looking for does not exist.</p>
        <a href="/restaurant/login" class="inline-flex rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Back to Home</a>
    </div>

    <script>
        document.getElementById('themeToggle').addEventListener('click', function () {
            const root = document.documentElement;
            const next = root.classList.contains('dark') ? 'light' : 'dark';
            root.classList.toggle('dark', next === 'dark');
            localStorage.setItem('theme', next);
        });
    </script>
</body>
</html>

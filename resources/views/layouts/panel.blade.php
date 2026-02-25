<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Panel' }}</title>
    @include('layouts.partials.theme-init')
    @include('layouts.partials.assets')
</head>
<body class="min-h-screen bg-gray-50 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
    <div class="min-h-screen">
        <aside id="sidebar" class="fixed top-0 left-0 z-50 flex h-screen w-[280px] -translate-x-full flex-col border-r border-gray-200 bg-white transition-transform duration-300 dark:border-gray-800 dark:bg-gray-900 xl:translate-x-0">
            <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $panelName ?? 'Dashboard' }}</h2>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-4">
                @php
                    $restaurant = auth('restaurant')->user();
                    $menuExists = \App\Models\MenuItem::where('restaurant_id', $restaurant->id)->exists();
                @endphp
                @foreach(($navLinks ?? []) as $link)

                    @if($link['route'] === 'menu.import.form' && $menuExists)
                        @continue
                    @endif
                    @php
                        $isActive = isset($link['active'])
                            ? request()->routeIs($link['active'])
                            : request()->routeIs($link['route']);
                    @endphp
                    <a href="{{ route($link['route']) }}"
                       class="flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $isActive ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/20 dark:text-brand-300' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5' }}">
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="border-t border-gray-200 p-4 dark:border-gray-800">
                <form method="POST" action="{{ route($logoutRoute ?? 'restaurant.logout') }}">
                    @csrf
                    <button class="flex w-full items-center justify-center rounded-lg bg-error-500 px-3 py-2 text-sm font-medium text-white hover:bg-error-600">
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <div id="sidebarOverlay" class="fixed inset-0 z-40 hidden bg-black/40 xl:hidden"></div>

        <div class="xl:ml-[280px]">
            <header class="sticky top-0 z-30 border-b border-gray-200 bg-white/95 backdrop-blur dark:border-gray-800 dark:bg-gray-900/95">
                <div class="flex items-center justify-between px-4 py-4 sm:px-6">
                    <div class="flex items-center gap-3">
                        <button id="menuToggle" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5 xl:hidden" type="button" aria-label="Open sidebar">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 5H17M3 10H17M3 15H13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            </svg>
                        </button>
                        <div>
                            <h1 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $heading ?? 'Dashboard' }}</h1>
                            @if(!empty($subheading))
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $subheading }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if(!empty($headerRight))
                            <div>{!! $headerRight !!}</div>
                        @endif
                        <button id="themeToggle" type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5" aria-label="Toggle theme">
                            <span class="dark:hidden">☀</span>
                            <span class="hidden dark:inline">☾</span>
                        </button>
                    </div>
                </div>
            </header>

            <main class="px-4 py-6 sm:px-6">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        (function () {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const toggle = document.getElementById('menuToggle');
            const themeToggle = document.getElementById('themeToggle');

            function openSidebar() {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            }

            function closeSidebar() {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }

            function applyTheme(theme) {
                if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
                localStorage.setItem('theme', theme);
            }

            if (toggle) {
                toggle.addEventListener('click', function () {
                    if (sidebar.classList.contains('-translate-x-full')) {
                        openSidebar();
                    } else {
                        closeSidebar();
                    }
                });
            }

            if (overlay) {
                overlay.addEventListener('click', closeSidebar);
            }

            if (themeToggle) {
                themeToggle.addEventListener('click', function () {
                    const next = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
                    applyTheme(next);
                });
            }

            window.addEventListener('resize', function () {
                if (window.innerWidth >= 1280) {
                    overlay.classList.add('hidden');
                    sidebar.classList.remove('-translate-x-full');
                } else {
                    sidebar.classList.add('-translate-x-full');
                }
            });
        })();
    </script>

    @stack('scripts')
</body>
</html>

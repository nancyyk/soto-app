<!DOCTYPE html>
<html lang="id" class="h-full"
    x-data="{ dark: localStorage.getItem('soto-theme') === 'dark' }"
    x-init="$watch('dark', v => { localStorage.setItem('soto-theme', v ? 'dark' : 'light'); document.documentElement.classList.toggle('dark', v) }); document.documentElement.classList.toggle('dark', dark)"
    :class="{ 'dark': dark }">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield("title", "Dashboard") ? SOTO Admin</title>
    @vite(["resources/css/app.css", "resources/js/app.js"])
    @livewireStyles
    <script src="https://cdn.jsdelivr.net/npm/apexcharts" defer></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>
    @stack("styles")
</head>
<body class="h-full bg-gray-100 dark:bg-gray-950 transition-colors duration-200">
<div class="flex h-full" x-data="{ sidebarOpen: false }">

    {{-- ?? Sidebar ???????????????????????????????????????????????????????? --}}
    <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700 z-30">
        @include("layouts.partials.sidebar")
    </aside>

    {{-- Mobile sidebar overlay --}}
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen=false"
         class="fixed inset-0 bg-black/40 z-20 lg:hidden"></div>
    <aside x-show="sidebarOpen" x-cloak
           class="fixed inset-y-0 left-0 w-64 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700 z-30 lg:hidden">
        @include("layouts.partials.sidebar")
    </aside>

    {{-- ?? Main Content ??????????????????????????????????????????????????? --}}
    <div class="flex flex-col flex-1 lg:pl-64 min-h-screen">

        {{-- Topbar --}}
        <header class="sticky top-0 z-10 flex items-center gap-4 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 px-4 py-3 shadow-sm">
            {{-- Mobile hamburger --}}
            <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            {{-- Page title --}}
            <h1 class="flex-1 text-base font-semibold text-gray-800 dark:text-gray-100">@yield("title", "Dashboard")</h1>

            {{-- ?? Right side controls ??????????????????????????????????? --}}
            <div class="flex items-center gap-3">

                {{-- Dark mode toggle --}}
                <button @click="dark = !dark"
                        class="relative w-9 h-9 flex items-center justify-center rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                        :title="dark ? 'Beralih ke mode terang' : 'Beralih ke mode gelap'">
                    {{-- Sun icon (shown in dark mode) --}}
                    <svg x-show="dark" class="w-5 h-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    {{-- Moon icon (shown in light mode) --}}
                    <svg x-show="!dark" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </button>

                {{-- Divider --}}
                <div class="w-px h-6 bg-gray-200 dark:bg-gray-700"></div>

                {{-- User info --}}
                <span class="text-sm text-gray-500 dark:text-gray-400 hidden sm:block">{{ auth()->user()?->nama }}</span>

                {{-- Logout --}}
                <form method="POST" action="{{ route("logout") }}">
                    @csrf
                    <button type="submit" class="text-sm text-red-500 hover:text-red-400 font-medium transition-colors">Keluar</button>
                </form>
            </div>
        </header>

        {{-- Page content --}}
        <main class="flex-1 p-6">
            @yield("content")
        </main>

        <footer class="text-center text-xs text-gray-400 dark:text-gray-600 py-4 border-t border-gray-100 dark:border-gray-800">
            SOTO Admin &copy; {{ date("Y") }} ? Sampah Otomatis Tukar Poin
        </footer>
    </div>
</div>
@livewireScripts
@stack("scripts")
</body>
</html>

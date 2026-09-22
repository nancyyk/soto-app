<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50" x-data="{ sidebarOpen: false }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - SOTO Admin</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full font-sans antialiased text-gray-900 bg-gray-50 overflow-hidden">
    
    <div class="flex h-full">
        <aside class="hidden md:block w-64 flex-shrink-0 z-20">
            @include('layouts.partials.sidebar')
        </aside>

        <div x-show="sidebarOpen" x-cloak class="fixed inset-0 bg-gray-900/80 z-40 md:hidden" @click="sidebarOpen = false" x-transition.opacity></div>

        <aside x-show="sidebarOpen" x-cloak class="fixed inset-y-0 left-0 w-64 z-50 md:hidden transform transition-transform" x-transition:enter="duration-300 ease-out" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="duration-200 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full">
            @include('layouts.partials.sidebar')
        </aside>

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Topbar with Page Title & Right Actions -->
            <header class="bg-white border-b border-gray-200 h-16 flex items-center px-4 sm:px-6 z-10 flex-shrink-0 shadow-sm">
                <button @click="sidebarOpen = true" class="md:hidden p-2 -ml-2 mr-3 rounded-md text-gray-500 hover:bg-gray-100">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <div class="flex-1 min-w-0">
                    <h1 class="text-lg font-semibold text-gray-900 tracking-tight truncate">@yield('title', 'Dashboard')</h1>
                </div>

                <!-- Top Right Actions -->
                <div class="flex items-center gap-3 ml-4 flex-shrink-0">
                    <!-- User Avatar -->
                    <div class="w-9 h-9 rounded-full bg-emerald-50 border border-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm shadow-sm cursor-help hover:bg-emerald-100 transition-colors" 
                         title="{{ Auth::user()->nama ?? 'Administrator' }} ({{ Auth::user()->email ?? '' }})">
                        {{ strtoupper(substr(Auth::user()->nama ?? 'A', 0, 1)) }}
                    </div>

                    <!-- Logout Button -->
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="w-9 h-9 rounded-full bg-red-50 hover:bg-red-100 border border-red-100 text-red-600 flex items-center justify-center shadow-sm transition-colors group" title="Keluar dari Aplikasi">
                            <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                @yield('content')
                
                @if(isset($slot))
                    {{ $slot }}
                @endif
            </main>
        </div>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
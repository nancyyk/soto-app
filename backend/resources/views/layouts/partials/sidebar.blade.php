<div class="flex flex-col h-full">
    {{-- Logo --}}
    <div class="flex items-center gap-3 px-4 py-5 border-b border-gray-200 dark:border-gray-700">
        <div class="w-8 h-8 rounded-lg bg-green-600 flex items-center justify-center text-white font-bold text-sm shadow">S</div>
        <div>
            <p class="font-bold text-gray-800 dark:text-gray-100 text-sm leading-tight">SOTO Admin</p>
            <p class="text-xs text-gray-400 dark:text-gray-500">Monitoring & Logistik</p>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        <p class="px-3 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Monitoring</p>

        <a href="{{ route("dashboard") }}"
           class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors
                  {{ request()->routeIs("dashboard") ? "bg-green-50 dark:bg-green-950 text-green-700 dark:text-green-400" : "text-gray-700 dark:text-gray-300 hover:bg-green-50 dark:hover:bg-gray-800 hover:text-green-700 dark:hover:text-green-400" }}">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/>
            </svg>
            Dashboard
        </a>

        <a href="{{ route("monitoring.index") }}"
           class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors
                  {{ request()->routeIs("monitoring.*") ? "bg-green-50 dark:bg-green-950 text-green-700 dark:text-green-400" : "text-gray-700 dark:text-gray-300 hover:bg-green-50 dark:hover:bg-gray-800 hover:text-green-700 dark:hover:text-green-400" }}">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2v-4M9 21H5a2 2 0 01-2-2v-4m0 0h18"/>
            </svg>
            Node RVM
        </a>

        <a href="{{ route("rute.index") }}"
           class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors
                  {{ request()->routeIs("rute.*") ? "bg-green-50 dark:bg-green-950 text-green-700 dark:text-green-400" : "text-gray-700 dark:text-gray-300 hover:bg-green-50 dark:hover:bg-gray-800 hover:text-green-700 dark:hover:text-green-400" }}">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
            </svg>
            Rute TSP
        </a>

        <a href="{{ route("transaksi.index") }}"
           class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors
                  {{ request()->routeIs("transaksi.*") ? "bg-green-50 dark:bg-green-950 text-green-700 dark:text-green-400" : "text-gray-700 dark:text-gray-300 hover:bg-green-50 dark:hover:bg-gray-800 hover:text-green-700 dark:hover:text-green-400" }}">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
            Transaksi
        </a>

        @if(auth()->user()?->isAdmin())
        <div class="pt-4">
            <p class="px-3 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Manajemen</p>
        </div>

        <a href="{{ route("pengguna.index") }}"
           class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors
                  {{ request()->routeIs("pengguna.*") ? "bg-green-50 dark:bg-green-950 text-green-700 dark:text-green-400" : "text-gray-700 dark:text-gray-300 hover:bg-green-50 dark:hover:bg-gray-800 hover:text-green-700 dark:hover:text-green-400" }}">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Pengguna
        </a>

        <a href="{{ route("pengaturan.index") }}"
           class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors
                  {{ request()->routeIs("pengaturan.*") ? "bg-green-50 dark:bg-green-950 text-green-700 dark:text-green-400" : "text-gray-700 dark:text-gray-300 hover:bg-green-50 dark:hover:bg-gray-800 hover:text-green-700 dark:hover:text-green-400" }}">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Pengaturan
        </a>
        @endif
    </nav>

    {{-- User Info --}}
    <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
        <p class="text-xs font-semibold text-gray-700 dark:text-gray-200 truncate">{{ auth()->user()?->nama }}</p>
        <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ auth()->user()?->role?->label() }}</p>
    </div>
</div>

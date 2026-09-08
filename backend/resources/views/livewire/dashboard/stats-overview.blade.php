<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

    <div class="stat-card">
        <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-900/40 flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalBotolHariIni) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Botol Hari Ini</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalPoinHariIni) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Poin Dikreditkan</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"/>
            </svg>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $nodeAktif }}<span class="text-base text-gray-400 dark:text-gray-500 font-normal">/5</span></p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Node Online</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="w-12 h-12 rounded-xl {{ $nodeKritis > 0 ? "bg-red-100 dark:bg-red-900/40" : "bg-gray-100 dark:bg-gray-700" }} flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 {{ $nodeKritis > 0 ? "text-red-600 dark:text-red-400" : "text-gray-400 dark:text-gray-500" }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <div>
            <p class="text-2xl font-bold {{ $nodeKritis > 0 ? "text-red-600 dark:text-red-400" : "text-gray-900 dark:text-white" }}">{{ $nodeKritis }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Node Perlu Dikosongkan</p>
        </div>
    </div>

</div>

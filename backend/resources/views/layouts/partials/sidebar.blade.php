<div class="flex flex-col h-full bg-white border-r border-gray-200">
    {{-- Logo --}}
    <div class="flex items-center gap-3 px-5 py-5 border-b border-gray-100">
        <div class="w-8 h-8 rounded bg-emerald-600 flex items-center justify-center text-white font-bold text-sm shadow-sm">S</div>
        <div>
            <p class="font-semibold tracking-tight text-gray-900 text-sm">SOTO Admin</p>
            <p class="text-[11px] text-gray-500 font-medium tracking-wide uppercase mt-0.5">Logistik</p>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        @php
            $navs = [
                ['name' => 'Ringkasan', 'route' => 'dashboard', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />'],
                ['name' => 'Monitoring IoT', 'route' => 'monitoring.index', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />'],
                ['name' => 'Peta Rute TSP', 'route' => 'rute.index', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />'],
                ['name' => 'Transaksi Poin', 'route' => 'transaksi.index', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'],
                ['name' => 'Pengguna', 'route' => 'pengguna.index', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />'],
                ['name' => 'Pengaturan', 'route' => 'pengaturan.index', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />'],
            ];
        @endphp

        @foreach($navs as $nav)
            @php $isActive = request()->routeIs($nav['route']); @endphp
            <a href="{{ route($nav['route']) }}" 
               class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors 
               {{ $isActive 
                  ? 'bg-gray-100 text-gray-900' 
                  : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="w-5 h-5 {{ $isActive ? 'text-emerald-600' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    {!! $nav['icon'] !!}
                </svg>
                {{ $nav['name'] }}
            </a>
        @endforeach
    </nav>
</div>
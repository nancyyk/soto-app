<div class="relative" x-data="{ open: false }">
    <!-- Bell Icon Button -->
    <button @click="open = !open" type="button" class="relative p-2 rounded-full text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none transition-colors" title="Notifikasi">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>

        @if($unreadCount > 0)
            <span class="absolute top-1 right-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <!-- Dropdown Menu -->
    <div x-show="open" 
         @click.away="open = false" 
         x-cloak
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         style="width: 56rem; max-width: calc(100vw - 2rem); right: -6rem;"
         class="absolute mt-2 bg-white rounded-xl shadow-xl border border-gray-200 z-50 overflow-hidden">
        
        <div class="shrink-0 p-4 sm:p-5 bg-gray-50 border-b border-gray-200 flex items-center justify-between gap-4">
            <h3 class="text-base font-semibold text-gray-800">Notifikasi</h3>
            @if($unreadCount > 0)
                <button wire:click="markAllAsRead" class="shrink-0 text-sm text-emerald-600 hover:text-emerald-800 font-medium transition-colors">
                    Tandai Semua Dibaca
                </button>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto overscroll-contain divide-y divide-gray-100">
            @forelse($notifications as $notif)
                @php
                    $data = $notif->data ?? [];
                    $isUnread = is_null($notif->read_at);
                    $isWarning = $data['warning'] ?? false;
                @endphp
                <div class="p-4 sm:p-5 transition-colors border-l-2 {{ $isUnread ? 'border-l-emerald-500 bg-emerald-50/50' : 'border-l-transparent bg-white hover:bg-gray-50' }}">
                    <div class="flex items-start gap-4 sm:gap-5">
                        <div class="p-1.5 sm:p-2 rounded-lg shrink-0 {{ $isWarning ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                            @if($isWarning)
                                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            @else
                                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_auto] items-start gap-x-4 mb-1.5">
                                <p class="min-w-0 text-sm font-semibold text-gray-900 leading-5 line-clamp-2">{{ $data['title'] ?? 'Notifikasi' }}</p>
                                <span class="justify-self-end sm:justify-self-auto whitespace-nowrap text-xs leading-5 text-gray-400">{{ $notif->created_at ? $notif->created_at->diffForHumans() : '' }}</span>
                            </div>
                            <p class="text-sm text-gray-600 line-clamp-2 leading-relaxed">{{ $data['description'] ?? ($data['message'] ?? '') }}</p>
                            
                            @if($isUnread)
                                <button wire:click="markAsRead('{{ $notif->id }}')" class="mt-2 inline-flex text-xs font-medium text-emerald-700 hover:text-emerald-900 hover:underline">
                                    Tandai Dibaca
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-6 text-center text-gray-400 text-xs">
                    Tidak ada notifikasi.
                </div>
            @endforelse
        </div>
    </div>
</div>

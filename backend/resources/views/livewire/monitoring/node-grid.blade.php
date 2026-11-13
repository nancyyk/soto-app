<div wire:poll.3s="refreshMachines" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
    @foreach($machines as $machine)
    @php
        $pct        = $machine->kapasitas_terkini;
        $online     = $machine->isOnline();
        $colorFill  = $pct >= 80 ? "bg-red-500" : ($pct >= 50 ? "bg-yellow-400" : "bg-green-500");
    @endphp
    <a href="{{ route("monitoring.show", $machine->id) }}"
       class="card p-4 hover:shadow-md hover:ring-2 hover:ring-emerald-400 transition-all block group">

        {{-- Header --}}
        <div class="flex items-start justify-between mb-3">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full {{ $online ? 'bg-emerald-500 animate-pulse' : 'bg-gray-400' }} flex-shrink-0"></span>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        {{ $machine->is_simulation ? "Simulasi" : "Real Device" }}
                    </p>
                </div>
                <p class="text-sm font-semibold text-gray-800 mt-0.5 leading-tight group-hover:text-emerald-700 transition-colors truncate" title="{{ $machine->nama_lokasi }}">
                    {{ $machine->nama_lokasi }}
                </p>
                @if(!$machine->is_simulation)
                    <p class="text-[10px] {{ $online ? 'text-emerald-600 font-medium' : 'text-gray-400' }} mt-0.5">
                        @if($machine->last_seen_at)
                            {{ $online ? 'Aktif ' . $machine->last_seen_at->diffForHumans() : 'Mati ' . $machine->last_seen_at->diffForHumans() }}
                        @else
                            Belum pernah aktif
                        @endif
                    </p>
                @endif
            </div>
            <span class="{{ $online ? "badge-green" : "badge-gray" }} ml-2 flex-shrink-0">
                {{ $online ? "ON" : "OFF" }}
            </span>
        </div>

        {{-- Capacity Bar --}}
        <div class="mb-2">
            <div class="flex justify-between text-xs mb-1">
                <span class="text-gray-500">Kapasitas</span>
                <span class="font-semibold {{ $pct >= 80 ? "text-red-600" : "text-gray-700" }}">{{ $pct }}%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill {{ $colorFill }}" style="width: {{ $pct }}%"></div>
            </div>
        </div>

        {{-- Battery & Status --}}
        <div class="flex items-center justify-between text-xs mt-3">
            <span class="text-gray-500 flex items-center gap-1">
                🔋 {{ $machine->tegangan_baterai ? number_format($machine->tegangan_baterai, 1)." V" : "-" }}
            </span>
            <span class="{{ $pct >= 80 ? "badge-red" : ($pct >= 50 ? "badge-yellow" : "badge-green") }}">
                {{ $pct >= 80 ? "Kritis" : ($pct >= 50 ? "Penuh" : "Normal") }}
            </span>
        </div>
    </a>
    @endforeach
</div>



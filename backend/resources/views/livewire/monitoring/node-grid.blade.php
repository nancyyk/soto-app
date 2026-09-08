<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
    @foreach($machines as $machine)
    @php
        $pct        = $machine->kapasitas_terkini;
        $online     = $machine->status_online;
        $colorFill  = $pct >= 80 ? "bg-red-500" : ($pct >= 50 ? "bg-yellow-400" : "bg-green-500");
    @endphp
    <a href="{{ route("monitoring.show", $machine->id) }}"
       class="card p-4 hover:shadow-md hover:ring-green-400 dark:hover:ring-green-600 transition-all block group">

        {{-- Header --}}
        <div class="flex items-start justify-between mb-3">
            <div>
                <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                    {{ $machine->is_simulation ? "Simulasi" : "Real Device" }}
                </p>
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-0.5 leading-tight group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors">
                    {{ Str::limit($machine->nama_lokasi, 26) }}
                </p>
            </div>
            <span class="{{ $online ? "badge-green" : "badge-gray" }} ml-2 flex-shrink-0">
                {{ $online ? "ON" : "OFF" }}
            </span>
        </div>

        {{-- Capacity Bar --}}
        <div class="mb-2">
            <div class="flex justify-between text-xs mb-1">
                <span class="text-gray-500 dark:text-gray-400">Kapasitas</span>
                <span class="font-semibold {{ $pct >= 80 ? "text-red-600 dark:text-red-400" : "text-gray-700 dark:text-gray-300" }}">{{ $pct }}%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill {{ $colorFill }}" style="width: {{ $pct }}%"></div>
            </div>
        </div>

        {{-- Battery & Status --}}
        <div class="flex items-center justify-between text-xs mt-3">
            <span class="text-gray-500 dark:text-gray-400">
                ?? {{ $machine->tegangan_baterai ? number_format($machine->tegangan_baterai, 1)." V" : "?" }}
            </span>
            <span class="{{ $pct >= 80 ? "badge-red" : ($pct >= 50 ? "badge-yellow" : "badge-green") }}">
                {{ $pct >= 80 ? "Kritis" : ($pct >= 50 ? "Penuh" : "Normal") }}
            </span>
        </div>
    </a>
    @endforeach
</div>

<div class="space-y-6">
    <div class="card p-5">
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex-1">
                <p class="text-xs text-gray-500  uppercase tracking-wider">{{ $machine->is_simulation ? "Node Simulasi" : "Real Device" }}</p>
                <h2 class="text-xl font-bold text-gray-900  mt-1">{{ $machine->nama_lokasi }}</h2>
                <p class="text-sm text-gray-500  mt-1 font-mono">{{ $machine->latitude }}, {{ $machine->longitude }}</p>
            </div>
            <div class="flex gap-4 flex-wrap">
                <div class="text-center">
                    <p class="text-3xl font-bold {{ $machine->kapasitas_terkini >= 80 ? "text-red-600 " : "text-green-600 " }}">{{ $machine->kapasitas_terkini }}%</p>
                    <p class="text-xs text-gray-500 ">Kapasitas</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold text-gray-700 ">{{ $machine->tegangan_baterai ? number_format($machine->tegangan_baterai, 1) : "?" }}</p>
                    <p class="text-xs text-gray-500 ">Volt</p>
                </div>
                <div class="text-center self-center">
                    <span class="{{ $machine->status_online ? "badge-green" : "badge-gray" }} text-sm px-3 py-1">
                        {{ $machine->status_online ? "Online" : "Offline" }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Panel Simulasi Kapasitas (hanya untuk node simulasi) --}}
    @if($machine->is_simulation)
    <div class="card p-5 border-2 border-dashed border-yellow-300 bg-yellow-50 ">
        <div class="flex items-center gap-2 mb-4">
            <span class="text-yellow-600  text-sm">⚡</span>
            <h3 class="text-sm font-semibold text-yellow-700 ">Simulasi Kapasitas</h3>
            <span class="ml-auto text-xs text-yellow-600  bg-yellow-100  px-2 py-0.5 rounded-full">Node Simulasi</span>
        </div>

        @if(session('sim_success'))
            <div class="mb-3 p-2 rounded-lg bg-green-50  border border-green-200  text-sm text-green-700 ">
                ✓ {{ session('sim_success') }}
            </div>
        @endif
        @if(session('sim_error'))
            <div class="mb-3 p-2 rounded-lg bg-red-50  border border-red-200  text-sm text-red-700 ">
                ✗ {{ session('sim_error') }}
            </div>
        @endif

        <div class="space-y-4">
            {{-- Input kapasitas --}}
            <div class="flex items-center gap-3">
                <label class="text-sm text-gray-600  w-24 flex-shrink-0">Kapasitas (%)</label>
                <input
                    wire:model.live="simKapasitas"
                    type="range"
                    min="0" max="100" step="1"
                    class="flex-1 accent-yellow-500"
                />
                <input
                    wire:model.live="simKapasitas"
                    type="number"
                    min="0" max="100"
                    class="w-16 text-center border border-gray-300  rounded-lg px-2 py-1 text-sm font-mono focus:ring-2 focus:ring-yellow-400 focus:border-transparent"
                />
                <span class="text-sm text-gray-500 ">%</span>
            </div>

            {{-- Quick preset buttons --}}
            <div class="flex flex-wrap gap-2">
                <span class="text-xs text-gray-500  self-center">Preset:</span>
                @foreach([0, 25, 50, 75, 90, 100] as $preset)
                    <button
                        wire:click="setPreset({{ $preset }})"
                        class="px-3 py-1 text-xs rounded-full border transition-all
                            {{ $preset >= 90 ? 'border-red-300 text-red-600  hover:bg-red-50 ' : ($preset >= 50 ? 'border-yellow-300 text-yellow-700  hover:bg-yellow-50 ' : 'border-green-300 text-green-700  hover:bg-green-50 ') }}"
                    >
                        {{ $preset }}%
                    </button>
                @endforeach
            </div>

            {{-- Tombol apply --}}
            <div class="flex items-center gap-3 pt-2 border-t border-yellow-200 ">
                <div class="flex-1">
                    <p class="text-xs text-yellow-600 ">
                        Nilai saat ini: <strong>{{ $machine->kapasitas_terkini }}%</strong>
                        → Akan diubah ke: <strong>{{ $simKapasitas }}%</strong>
                    </p>
                </div>
                <button
                    wire:click="simulateCapacity"
                    wire:loading.attr="disabled"
                    class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-semibold rounded-lg transition-colors disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="simulateCapacity">Terapkan</span>
                    <span wire:loading wire:target="simulateCapacity">Memproses...</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    <div class="card p-5">
        <h3 class="text-sm font-semibold text-gray-700  mb-4">Historis Kapasitas — 24 Jam Terakhir</h3>
        @if(count($chartData["labels"] ?? []) > 0)
            <div id="detail-chart" class="h-56" wire:ignore></div>
        @else
            <p class="text-sm text-gray-400  text-center py-10">Belum ada data historis dalam 24 jam terakhir.</p>
        @endif
    </div>
</div>


@if(count($chartData["labels"] ?? []) > 0)
@push("scripts")
<script>
(function() {
    if (typeof ApexCharts === "undefined") { setTimeout(arguments.callee, 200); return; }
    const isDark = document.documentElement.classList.contains("dark");
    const textColor = isDark ? "#9ca3af" : "#6b7280";
    const gridColor = isDark ? "#374151" : "#f3f4f6";
    new ApexCharts(document.getElementById("detail-chart"), {
        chart: { type: "line", height: 200, toolbar: { show: false }, background: "transparent" },
        series: [
            { name: "Kapasitas (%)", data: @json($chartData["kapasitas"]) },
            { name: "Baterai (V)",   data: @json($chartData["baterai"]) }
        ],
        xaxis: {
            categories: @json($chartData["labels"]),
            labels: { style: { fontSize: "10px", colors: textColor }, rotate: -30 }
        },
        yaxis: { labels: { style: { colors: textColor } } },
        colors: ["#16a34a", "#2563eb"],
        stroke: { curve: "smooth", width: 2 },
        dataLabels: { enabled: false },
        grid: { borderColor: gridColor },
        tooltip: { theme: isDark ? "dark" : "light" },
    }).render();
})();
</script>
@endpush
@endif


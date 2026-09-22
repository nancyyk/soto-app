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

    <div class="card p-5">
        <h3 class="text-sm font-semibold text-gray-700  mb-4">Historis Kapasitas ? 24 Jam Terakhir</h3>
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


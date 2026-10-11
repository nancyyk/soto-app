<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-base font-semibold text-gray-800 ">Optimasi Rute TSP (Traveling Salesperson Problem)</h2>
            <p class="text-sm text-gray-500 ">Rute sirkuit terpendek pengangkutan node dengan kapasitas &ge; threshold ({{ $threshold }}%)</p>
        </div>
        <button wire:click="recalculate" wire:loading.attr="disabled" class="btn-primary">
            <svg class="w-4 h-4 {{ $calculating ? "animate-spin" : "" }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <span wire:loading.remove wire:target="recalculate">Hitung Ulang Rute</span>
            <span wire:loading wire:target="recalculate">Menghitung TSP...</span>
        </button>
    </div>

    @if(session("success"))
        <div class="p-3 rounded-lg bg-green-50  border border-green-200  text-sm text-green-700 ">
            ✓ {{ session("success") }}
        </div>
    @endif
    @if(session("info"))
        <div class="p-3 rounded-lg bg-blue-50  border border-blue-200  text-sm text-blue-700 ">
            ℹ️ {{ session("info") }}
        </div>
    @endif
    @if(session("error"))
        <div class="p-3 rounded-lg bg-red-50  border border-red-200  text-sm text-red-700 ">
            ✗ {{ session("error") }}
        </div>
    @endif

    {{-- Peta Leaflet --}}
    <div class="card overflow-hidden">
        <div class="p-4 border-b border-gray-100  flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-semibold text-gray-700 ">Peta Visualisasi Rute</h3>
                @if(($mapData['distanceSource'] ?? '') === 'osrm')
                    <span class="text-xs bg-emerald-100  text-emerald-700  px-2.5 py-0.5 rounded-full font-semibold flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        🛣️ OSRM Road Routing (Jalan Aspal Asli)
                    </span>
                @else
                    <span class="text-xs bg-gray-100  text-gray-600  px-2 py-0.5 rounded-full font-mono">
                        📏 Haversine (Garis Lurus)
                    </span>
                @endif
            </div>
            {{-- Legenda --}}
            <div class="flex items-center gap-4 text-xs text-gray-600 ">
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-green-600 inline-block"></span> Pos Depot
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-red-600 inline-block"></span> Kunjungan (&ge; {{ $threshold }}%)
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-slate-500 inline-block"></span> Diabaikan (&lt; {{ $threshold }}%)
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-4 h-1 bg-blue-600 rounded inline-block"></span>
                    {{ ($mapData['distanceSource'] ?? '') === 'osrm' ? 'Jalur Jalan Aspal' : 'Jalur Sirkuit' }}
                </span>
            </div>
        </div>
        <div id="rute-map" class="h-96 w-full z-0" wire:ignore></div>
    </div>

    {{-- Ringkasan Filter Node --}}
    <div class="card p-5">
        <h3 class="text-sm font-semibold text-gray-700  mb-3">
            Analisis Filter Node (Threshold: &ge; {{ $threshold }}%)
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
            {{-- Depot --}}
            <div class="p-3 rounded-lg border border-green-200 bg-green-50 ">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-bold text-green-700 ">Pos Depot (v0)</span>
                    <span class="w-5 h-5 rounded-full bg-green-600 text-white text-[10px] font-bold flex items-center justify-center">D</span>
                </div>
                <p class="text-xs text-gray-600  truncate">{{ $mapData['depot']['nama'] ?? 'Pos Pengangkutan' }}</p>
                <span class="inline-block mt-2 text-[10px] font-semibold text-green-700 bg-green-100  px-1.5 py-0.5 rounded">
                    Awal & Akhir
                </span>
            </div>

            {{-- Mesin --}}
            @foreach($machines as $m)
                @php
                    $isFull = (int) $m['kapasitas_terkini'] >= $threshold;
                    $stopUrutan = collect($mapData['stops'] ?? [])->firstWhere('machine_id', $m['id'])['urutan'] ?? null;
                @endphp
                <div class="p-3 rounded-lg border transition-all {{ $isFull ? 'border-red-200 bg-red-50 ' : 'border-gray-200 bg-gray-50 ' }}">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-bold {{ $isFull ? 'text-red-700 ' : 'text-gray-700 ' }}">
                            {{ $m['is_simulation'] ? 'Simulasi' : 'Real' }} #{{ $m['id'] }}
                        </span>
                        <span class="text-xs font-bold font-mono {{ $isFull ? 'text-red-600 ' : 'text-gray-500 ' }}">
                            {{ $m['kapasitas_terkini'] }}%
                        </span>
                    </div>
                    <p class="text-xs text-gray-600  truncate" title="{{ $m['nama_lokasi'] }}">{{ $m['nama_lokasi'] }}</p>
                    <div class="mt-2 flex items-center justify-between">
                        @if($isFull)
                            <span class="text-[10px] font-semibold text-red-700 bg-red-100  px-1.5 py-0.5 rounded">
                                Masuk Rute {{ $stopUrutan ? "(Stop #{$stopUrutan})" : '' }}
                            </span>
                        @else
                            <span class="text-[10px] font-medium text-gray-500 bg-gray-200  px-1.5 py-0.5 rounded">
                                Diabaikan
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if($latestRoute && $latestRoute->stops->isNotEmpty())
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="card p-4 text-center">
            <p class="text-2xl font-bold text-green-600 ">{{ number_format($latestRoute->total_distance_km, 2) }} km</p>
            <p class="text-xs text-gray-500  mt-1">
                {{ ($latestRoute->distance_source ?? '') === 'osrm' ? 'Total Jarak Jalan Aspal (OSRM)' : 'Total Jarak Sirkuit (Haversine)' }}
            </p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-2xl font-bold text-blue-600 ">{{ $latestRoute->total_duration_min }} menit</p>
            <p class="text-xs text-gray-500  mt-1">Estimasi Waktu Pengangkutan</p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-2xl font-bold text-gray-700 ">{{ $latestRoute->stops->count() }} Node</p>
            <p class="text-xs text-gray-500  mt-1">Jumlah Titik Singgah</p>
        </div>
    </div>

    <div class="card">
        <div class="p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-700 ">Urutan Kunjungan Optimum</h3>
                <span class="text-xs text-gray-400">Algoritma: Exact Permutasi TSP</span>
            </div>
            <ol class="space-y-3">
                <li class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-full bg-green-600 text-white text-xs font-bold flex items-center justify-center flex-shrink-0 shadow-sm">D</span>
                    <div>
                        <p class="text-sm font-semibold text-gray-800 ">{{ $mapData['depot']['nama'] ?? 'Pos Pengangkutan' }} (Depot)</p>
                        <p class="text-xs text-gray-400">Titik Awal Keberangkatan</p>
                    </div>
                    <span class="ml-auto text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded">Start</span>
                </li>

                @foreach($latestRoute->stops->sortBy("urutan") as $stop)
                <li class="flex items-center gap-3 pl-2 border-l-2 border-blue-200 ml-4">
                    <span class="w-8 h-8 rounded-full bg-blue-600 text-white text-xs font-bold flex items-center justify-center flex-shrink-0 shadow-sm -ml-6">
                        {{ $stop->urutan }}
                    </span>
                    <div class="ml-2">
                        <p class="text-sm font-semibold text-gray-800 ">{{ $stop->machine->nama_lokasi }}</p>
                        <p class="text-xs text-gray-400 font-mono">{{ $stop->machine->latitude }}, {{ $stop->machine->longitude }}</p>
                    </div>
                    <div class="ml-auto flex items-center gap-2">
                        <span class="badge-red font-mono text-xs">{{ $stop->machine->kapasitas_terkini }}%</span>
                    </div>
                </li>
                @endforeach

                <li class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-full bg-green-600 text-white text-xs font-bold flex items-center justify-center flex-shrink-0 shadow-sm">D</span>
                    <div>
                        <p class="text-sm font-semibold text-gray-800 ">{{ $mapData['depot']['nama'] ?? 'Pos Pengangkutan' }} (Depot)</p>
                        <p class="text-xs text-gray-400">Kembali ke Pos Depot (Sirkuit Tertutup)</p>
                    </div>
                    <span class="ml-auto text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded">Finish</span>
                </li>
            </ol>
        </div>
    </div>
    @else
        <div class="card p-8 text-center text-gray-400  text-sm">
            <p class="text-base font-semibold text-gray-600  mb-1">Belum ada rute tersimpan</p>
            <p>Pastikan terdapat mesin yang memiliki kapasitas &ge; {{ $threshold }}%, lalu klik tombol <strong>"Hitung Ulang Rute"</strong> di atas.</p>
        </div>
    @endif
</div>

@push("scripts")
<script>
let ruteMap = null;
let rutePolyline = null;
let ruteMarkersLayer = null;

function renderRuteMap(mapData) {
    if (typeof L === "undefined") {
        setTimeout(() => renderRuteMap(mapData), 150);
        return;
    }

    const container = document.getElementById("rute-map");
    if (!container) return;

    if (!ruteMap) {
        ruteMap = L.map("rute-map").setView([mapData.depot.lat, mapData.depot.lng], 14);

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "&copy; OpenStreetMap contributors"
        }).addTo(ruteMap);

        ruteMarkersLayer = L.layerGroup().addTo(ruteMap);
    }

    ruteMarkersLayer.clearLayers();
    if (rutePolyline) {
        ruteMap.removeLayer(rutePolyline);
        rutePolyline = null;
    }

    // Stop order lookup
    const stopMap = {};
    if (mapData.stops && mapData.stops.length) {
        mapData.stops.forEach(s => {
            stopMap[s.machine_id] = s.urutan;
        });
    }

    const bounds = L.latLngBounds([[mapData.depot.lat, mapData.depot.lng]]);

    // Depot marker
    const depotIcon = L.divIcon({
        className: "",
        html: `<div style="background:#16a34a;color:white;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:bold;box-shadow:0 3px 8px rgba(0,0,0,.35);border:2px solid white">D</div>`,
        iconSize: [34, 34],
        iconAnchor: [17, 17]
    });
    L.marker([mapData.depot.lat, mapData.depot.lng], { icon: depotIcon })
        .addTo(ruteMarkersLayer)
        .bindPopup(`<b>${mapData.depot.nama}</b><br><span style="color:#16a34a;font-weight:600">Pos Depot (Awal & Akhir)</span>`);

    // Machines markers
    if (mapData.machines) {
        mapData.machines.forEach(m => {
            const order = stopMap[m.id];
            const isEligible = order !== undefined;
            const color = isEligible ? "#dc2626" : (m.kapasitas_terkini >= 50 ? "#d97706" : "#64748b");
            const badgeLabel = isEligible ? `#${order}` : `${m.kapasitas_terkini}%`;

            const icon = L.divIcon({
                className: "",
                html: `<div style="background:${color};color:white;min-width:32px;height:32px;padding:0 6px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:bold;box-shadow:0 2px 6px rgba(0,0,0,.3);border:2px solid white">${badgeLabel}</div>`,
                iconSize: [32, 32],
                iconAnchor: [16, 16]
            });

            const latLng = [parseFloat(m.latitude), parseFloat(m.longitude)];
            bounds.extend(latLng);

            let popup = `<b>${m.nama_lokasi}</b><br>Kapasitas: <b>${m.kapasitas_terkini}%</b>`;
            if (isEligible) {
                popup += `<br><span style="color:#2563eb;font-weight:bold">Kunjungan ke-${order}</span>`;
            } else {
                popup += `<br><span style="color:#64748b">Diabaikan (&lt; ${mapData.threshold}%)</span>`;
            }

            L.marker(latLng, { icon })
                .addTo(ruteMarkersLayer)
                .bindPopup(popup);
        });
    }

    // Polyline
    if (mapData.polyline && mapData.polyline.length > 1) {
        const isOsrm = (mapData.distanceSource === "osrm");
        rutePolyline = L.polyline(mapData.polyline, {
            color: "#2563eb",
            weight: isOsrm ? 5 : 4,
            opacity: 0.9,
            dashArray: isOsrm ? null : "8, 6",
            lineJoin: "round"
        }).addTo(ruteMap);

        ruteMap.fitBounds(rutePolyline.getBounds().pad(0.15));
    } else {
        ruteMap.fitBounds(bounds.pad(0.15));
    }
}

document.addEventListener("DOMContentLoaded", () => {
    renderRuteMap(@json($mapData));
});
document.addEventListener("livewire:navigated", () => {
    renderRuteMap(@json($mapData));
});
window.addEventListener("routeUpdated", (e) => {
    renderRuteMap(e.detail.mapData);
});
</script>
@endpush



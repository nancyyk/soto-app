<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-base font-semibold text-gray-800 ">Optimasi Rute TSP</h2>
            <p class="text-sm text-gray-500 ">Rute pengangkutan node kapasitas &ge; threshold</p>
        </div>
        <button wire:click="recalculate" wire:loading.attr="disabled" class="btn-primary">
            <svg class="w-4 h-4 {{ $calculating ? "animate-spin" : "" }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            {{ $calculating ? "Menghitung..." : "Hitung Ulang Rute" }}
        </button>
    </div>

    @if(session("info"))
        <div class="p-3 rounded-lg bg-blue-50  border border-blue-200  text-sm text-blue-700 ">{{ session("info") }}</div>
    @endif

    {{-- Peta Leaflet --}}
    <div class="card">
        <div class="p-4 border-b border-gray-100 ">
            <h3 class="text-sm font-semibold text-gray-700 ">Peta Rute</h3>
        </div>
        <div id="rute-map" class="h-96 z-0" wire:ignore></div>
    </div>

    @if($latestRoute)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="card p-4 text-center">
            <p class="text-2xl font-bold text-green-600 ">{{ number_format($latestRoute->total_distance_km, 1) }} km</p>
            <p class="text-xs text-gray-500  mt-1">Total Jarak</p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-2xl font-bold text-blue-600 ">{{ $latestRoute->total_duration_min }} menit</p>
            <p class="text-xs text-gray-500  mt-1">Estimasi Waktu</p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-2xl font-bold text-gray-700 ">{{ $latestRoute->stops->count() }}</p>
            <p class="text-xs text-gray-500  mt-1">Jumlah Stop</p>
        </div>
    </div>

    <div class="card">
        <div class="p-5">
            <h3 class="text-sm font-semibold text-gray-700  mb-4">Urutan Kunjungan</h3>
            <ol class="space-y-2">
                <li class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-full bg-green-600 text-white text-xs font-bold flex items-center justify-center flex-shrink-0">D</span>
                    <span class="text-sm text-gray-700 ">Pos Pengangkutan (Depot)</span>
                </li>
                @foreach($latestRoute->stops->sortBy("urutan") as $stop)
                <li class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-full bg-gray-200  text-gray-700  text-xs font-bold flex items-center justify-center flex-shrink-0">{{ $loop->iteration }}</span>
                    <span class="text-sm text-gray-700 ">{{ $stop->machine->nama_lokasi }}</span>
                    <span class="{{ $stop->machine->kapasitas_terkini >= 80 ? "badge-red" : "badge-yellow" }} ml-auto">{{ $stop->machine->kapasitas_terkini }}%</span>
                </li>
                @endforeach
                <li class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-full bg-green-600 text-white text-xs font-bold flex items-center justify-center flex-shrink-0">D</span>
                    <span class="text-sm text-gray-500 ">Kembali ke Depot</span>
                </li>
            </ol>
        </div>
    </div>
    @else
        <div class="card p-8 text-center text-gray-400  text-sm">
            Belum ada rute tersimpan. Klik "Hitung Ulang Rute" untuk memulai kalkulasi TSP.
        </div>
    @endif
</div>

@push("scripts")
<script>
(function initMap() {
    if (typeof L === "undefined") { setTimeout(initMap, 200); return; }
    const mapData = @json($mapData);
    const isDark  = document.documentElement.classList.contains("dark");

    const map = L.map("rute-map").setView([mapData.depot.lat, mapData.depot.lng], 14);

    // Light / dark tile layers
    const lightTile = L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "&copy; OpenStreetMap contributors"
    });
    const darkTile = L.tileLayer("https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png", {
        attribution: "&copy; OpenStreetMap contributors &copy; CARTO"
    });

    (isDark ? darkTile : lightTile).addTo(map);

    // Toggle tile layer when dark mode changes
    const obs = new MutationObserver(() => {
        const d = document.documentElement.classList.contains("dark");
        if (d) { map.removeLayer(lightTile); darkTile.addTo(map); }
        else   { map.removeLayer(darkTile); lightTile.addTo(map); }
    });
    obs.observe(document.documentElement, { attributes: true, attributeFilter: ["class"] });

    const depotIcon = L.divIcon({ className: "", html: `<div style="background:#16a34a;color:white;width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:bold;box-shadow:0 2px 6px rgba(0,0,0,.4)">D</div>`, iconSize:[30,30], iconAnchor:[15,15] });
    L.marker([mapData.depot.lat, mapData.depot.lng], { icon: depotIcon }).addTo(map)
        .bindPopup(`<b>${mapData.depot.nama}</b><br>Depot`);

    mapData.machines.forEach(m => {
        const color = m.kapasitas_terkini >= 80 ? "#dc2626" : m.kapasitas_terkini >= 50 ? "#d97706" : "#16a34a";
        const icon = L.divIcon({ className: "", html: `<div style="background:${color};color:white;width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:bold;box-shadow:0 2px 6px rgba(0,0,0,.4)">${m.kapasitas_terkini}%</div>`, iconSize:[30,30], iconAnchor:[15,15] });
        L.marker([m.latitude, m.longitude], { icon }).addTo(map)
            .bindPopup(`<b>${m.nama_lokasi}</b><br>Kapasitas: ${m.kapasitas_terkini}%<br>${m.status_online ? "?? Online" : "? Offline"}`);
    });

    let polyline = null;
    if (mapData.polyline.length > 1) {
        polyline = L.polyline(mapData.polyline, { color: "#2563eb", weight: 3, dashArray: "8 5" }).addTo(map);
        map.fitBounds(polyline.getBounds().pad(0.12));
    }

    document.addEventListener("routeUpdated", e => {
        if (polyline) map.removeLayer(polyline);
        if (e.detail.mapData.polyline.length > 1)
            polyline = L.polyline(e.detail.mapData.polyline, { color: "#2563eb", weight: 3, dashArray: "8 5" }).addTo(map);
    });
})();
</script>
@endpush


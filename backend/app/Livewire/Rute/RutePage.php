<?php

namespace App\Livewire\Rute;

use App\Models\Machine;
use App\Models\Route;
use App\Models\Setting;
use App\Services\TspSolverService;
use Livewire\Attributes\On;
use Livewire\Component;

class RutePage extends Component
{
    public ?Route $latestRoute = null;

    public array $machines = [];

    public array $mapData = [];

    public int $threshold = 80;

    public bool $calculating = false;

    public function mount(): void
    {
        $this->loadData();
    }

    #[On('echo-private:admin-dashboard,.RouteRecalculated')]
    public function handleRouteUpdate(): void
    {
        $this->calculating = false;
        $this->loadData();
        $this->dispatch('routeUpdated', mapData: $this->mapData);
    }

    public function loadData(): void
    {
        $this->threshold = (int) Setting::get('tsp_threshold', 80);
        $this->latestRoute = Route::with('stops.machine')->latest('id')->first();
        $this->machines = Machine::all(['id', 'nama_lokasi', 'latitude', 'longitude', 'kapasitas_terkini', 'threshold_capacity', 'status_online', 'is_simulation'])->toArray();

        // Build map data for Leaflet
        $depot = [
            'lat' => (float) Setting::get('depot_lat', -7.2750),
            'lng' => (float) Setting::get('depot_lng', 112.7900),
            'nama' => Setting::get('depot_nama', 'Pos Pengangkutan'),
        ];

        $routePolyline = [];
        $stopsOrdered  = [];
        $distanceSource = 'haversine';

        if ($this->latestRoute && $this->latestRoute->stops->isNotEmpty()) {
            // Gunakan polyline jalan aspal yang sudah disimpan dari OSRM
            $routePolyline  = $this->latestRoute->route_geometry ?? [];
            $distanceSource = $this->latestRoute->distance_source ?? 'haversine';

            // Fallback: bangun garis lurus jika geometry belum ada (route lama sebelum OSRM)
            if (empty($routePolyline)) {
                $routePolyline[] = [$depot['lat'], $depot['lng']];
                foreach ($this->latestRoute->stops->sortBy('urutan') as $stop) {
                    if ($stop->machine) {
                        $routePolyline[] = [(float) $stop->machine->latitude, (float) $stop->machine->longitude];
                    }
                }
                $routePolyline[] = [$depot['lat'], $depot['lng']];
            }

            foreach ($this->latestRoute->stops->sortBy('urutan') as $stop) {
                if ($stop->machine) {
                    $stopsOrdered[] = [
                        'urutan'     => $stop->urutan,
                        'machine_id' => $stop->machine_id,
                        'nama'       => $stop->machine->nama_lokasi,
                        'lat'        => (float) $stop->machine->latitude,
                        'lng'        => (float) $stop->machine->longitude,
                        'kapasitas'  => (int) $stop->machine->kapasitas_terkini,
                    ];
                }
            }
        }

        $this->mapData = [
            'depot'          => $depot,
            'machines'       => $this->machines,
            'polyline'       => $routePolyline,
            'stops'          => $stopsOrdered,
            'threshold'      => $this->threshold,
            'distanceSource' => $distanceSource,
        ];
    }

    public function recalculate(): void
    {
        $this->calculating = true;

        $depot = [
            'lat' => (float) Setting::get('depot_lat', config('soto.depot_lat', -7.2750)),
            'lng' => (float) Setting::get('depot_lng', config('soto.depot_lng', 112.7900)),
            'nama' => Setting::get('depot_nama', 'Pos Pengangkutan'),
        ];

        try {
            $route = app(TspSolverService::class)->solveAndPersist($depot);

            $this->loadData();
            $this->calculating = false;

            if ($route) {
                session()->flash('success', "Rute TSP berhasil dihitung! Total jarak: {$route->total_distance_km} km ({$route->stops->count()} stop kunjungan).");
            } else {
                session()->flash('info', "Tidak ada mesin yang kapasitasnya mencapai threshold (≥ {$this->threshold}%). Rute tidak dibuat.");
            }

            $this->dispatch('routeUpdated', mapData: $this->mapData);
        } catch (\Throwable $e) {
            $this->calculating = false;
            session()->flash('error', 'Gagal menghitung rute TSP: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.rute.rute-page');
    }
}

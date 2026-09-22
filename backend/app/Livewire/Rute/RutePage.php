<?php

namespace App\Livewire\Rute;

use App\Jobs\RecalculateRouteJob;
use App\Models\Machine;
use App\Models\Route;
use App\Models\Setting;
use Livewire\Attributes\On;
use Livewire\Component;

class RutePage extends Component
{
    public ?Route $latestRoute = null;

    public array $machines = [];

    public array $mapData = [];

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
        $this->latestRoute = Route::with('stops.machine')->latest()->first();
        $this->machines = Machine::all(['id', 'nama_lokasi', 'latitude', 'longitude', 'kapasitas_terkini', 'status_online', 'is_simulation'])->toArray();

        // Build map data for Leaflet
        $depot = [
            'lat' => (float) Setting::get('depot_lat', -7.275),
            'lng' => (float) Setting::get('depot_lng', 112.790),
            'nama' => Setting::get('depot_nama', 'Pos Pengangkutan'),
        ];

        $routePolyline = [];
        if ($this->latestRoute) {
            $routePolyline[] = [$depot['lat'], $depot['lng']];
            foreach ($this->latestRoute->stops->sortBy('urutan') as $stop) {
                $routePolyline[] = [(float) $stop->machine->latitude, (float) $stop->machine->longitude];
            }
            $routePolyline[] = [$depot['lat'], $depot['lng']];
        }

        $this->mapData = [
            'depot' => $depot,
            'machines' => $this->machines,
            'polyline' => $routePolyline,
        ];
    }

    public function recalculate(): void
    {
        $this->calculating = true;
        RecalculateRouteJob::dispatch();
        session()->flash('info', 'Kalkulasi TSP sedang diproses...');
    }

    public function render()
    {
        return view('livewire.rute.rute-page');
    }
}

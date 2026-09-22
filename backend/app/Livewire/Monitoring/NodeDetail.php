<?php

namespace App\Livewire\Monitoring;

use App\Models\LogCapacity;
use App\Models\Machine;
use Livewire\Component;

class NodeDetail extends Component
{
    public Machine $machine;

    public array $chartData = [];

    public function mount(Machine $machine): void
    {
        $this->machine = $machine;
        $this->loadChart();
    }

    public function loadChart(): void
    {
        $logs = LogCapacity::where('machine_id', $this->machine->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->orderBy('created_at')
            ->get(['created_at', 'persen_kapasitas', 'tegangan_baterai']);

        $this->chartData = [
            'labels' => $logs->map(fn ($l) => $l->created_at->format('H:i'))->toArray(),
            'kapasitas' => $logs->pluck('persen_kapasitas')->toArray(),
            'baterai' => $logs->pluck('tegangan_baterai')->toArray(),
        ];
    }

    public function render()
    {
        return view('livewire.monitoring.node-detail');
    }
}

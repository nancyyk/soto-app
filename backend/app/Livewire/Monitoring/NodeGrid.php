<?php

namespace App\Livewire\Monitoring;

use App\Models\Machine;
use Livewire\Attributes\On;
use Livewire\Component;

class NodeGrid extends Component
{
    public $machines;

    public function mount(): void
    {
        $this->machines = Machine::orderBy('id')->get();
    }

    #[On('echo-private:admin-dashboard,.NodeTelemetryUpdated')]
    public function handleTelemetry(array $event): void
    {
        $this->machines = $this->machines->map(function ($m) use ($event) {
            if ($m->id === $event['machine_id']) {
                $m->kapasitas_terkini = $event['kapasitas'];
                $m->status_online = $event['status_online'];
                $m->tegangan_baterai = $event['tegangan_baterai'];
            }

            return $m;
        });
    }

    public function render()
    {
        return view('livewire.monitoring.node-grid');
    }
}

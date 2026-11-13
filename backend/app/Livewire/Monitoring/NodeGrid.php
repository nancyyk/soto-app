<?php

namespace App\Livewire\Monitoring;

use App\Models\Machine;
use Livewire\Attributes\On;
use Livewire\Component;

class NodeGrid extends Component
{
    public function refreshMachines(): void
    {
        // Mark stale real devices (no telemetry/heartbeat > 30s) as offline in DB
        Machine::stale()->update(['status_online' => false]);
    }

    #[On('echo-private:admin-dashboard,.NodeTelemetryUpdated')]
    public function handleTelemetry(array $event): void
    {
        $this->refreshMachines();
    }

    public function render()
    {
        Machine::stale()->update(['status_online' => false]);
        $machines = Machine::orderBy('id')->get();

        return view('livewire.monitoring.node-grid', [
            'machines' => $machines,
        ]);
    }
}


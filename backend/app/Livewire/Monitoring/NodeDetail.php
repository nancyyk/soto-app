<?php

namespace App\Livewire\Monitoring;

use App\Models\LogCapacity;
use App\Models\Machine;
use App\Services\NotificationService;
use Livewire\Attributes\On;
use Livewire\Component;

class NodeDetail extends Component
{
    public Machine $machine;

    public array $chartData = [];

    public int $simKapasitas = 0;

    public function mount(Machine $machine): void
    {
        $this->machine = $machine;
        $this->simKapasitas = (int) $machine->kapasitas_terkini;
        $this->refreshMachine();
    }

    public function refreshMachine(): void
    {
        Machine::stale()->update(['status_online' => false]);
        $this->machine->refresh();
        $this->loadChart();
    }

    #[On('echo-private:admin-dashboard,.NodeTelemetryUpdated')]
    public function handleTelemetry(array $event): void
    {
        if ($this->machine->id === ($event['machine_id'] ?? null)) {
            $this->refreshMachine();
        }
    }

    public function loadChart(): void
    {
        $logs = LogCapacity::where('machine_id', $this->machine->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->orderBy('created_at')
            ->get(['created_at', 'persen_kapasitas', 'tegangan_baterai']);

        $this->chartData = [
            'labels'    => $logs->map(fn ($l) => $l->created_at->format('H:i'))->toArray(),
            'kapasitas' => $logs->pluck('persen_kapasitas')->toArray(),
            'baterai'   => $logs->pluck('tegangan_baterai')->toArray(),
        ];
    }

    public function setPreset(int $value): void
    {
        $this->simKapasitas = max(0, min(100, $value));
    }

    public function simulateCapacity(): void
    {
        if (! $this->machine->is_simulation) {
            session()->flash('sim_error', 'Mesin ini bukan node simulasi.');
            return;
        }

        $kapasitas = max(0, min(100, (int) $this->simKapasitas));

        $oldCapacity = (int) $this->machine->kapasitas_terkini;
        $this->machine->update(['kapasitas_terkini' => $kapasitas]);

        NotificationService::checkAndNotifyMachineCapacityTransition($this->machine, $oldCapacity);

        $this->machine->refresh();
        $this->loadChart();

        session()->flash('sim_success', "Kapasitas {$this->machine->nama_lokasi} berhasil diubah ke {$kapasitas}%.");
    }

    public function render()
    {
        return view('livewire.monitoring.node-detail');
    }
}

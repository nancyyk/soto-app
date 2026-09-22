<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NodeTelemetryUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $machineId,
        public readonly int $kapasitas,
        public readonly bool $statusOnline,
        public readonly ?float $teganganBaterai,
        public readonly string $namaLokasi,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin-dashboard')];
    }

    public function broadcastAs(): string
    {
        return 'NodeTelemetryUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'machine_id' => $this->machineId,
            'kapasitas' => $this->kapasitas,
            'status_online' => $this->statusOnline,
            'tegangan_baterai' => $this->teganganBaterai,
            'nama_lokasi' => $this->namaLokasi,
        ];
    }
}

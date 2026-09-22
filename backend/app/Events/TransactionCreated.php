<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly int $machineId,
        public readonly int $jumlahBotol,
        public readonly int $poinDiperoleh,
        public readonly string $namaUser,
        public readonly string $namaLokasi,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin-dashboard')];
    }

    public function broadcastAs(): string
    {
        return 'TransactionCreated';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'machine_id' => $this->machineId,
            'jumlah_botol' => $this->jumlahBotol,
            'poin_diperoleh' => $this->poinDiperoleh,
            'nama_user' => $this->namaUser,
            'nama_lokasi' => $this->namaLokasi,
        ];
    }
}

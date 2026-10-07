<?php

namespace App\Notifications;

use App\Models\Machine;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MachineFullNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Machine $machine
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'machine_full',
            'title' => 'Mesin Penuh',
            'description' => "Mesin SOTO di {$this->machine->nama_lokasi} sedang penuh ({$this->machine->kapasitas_terkini}%)",
            'machine_id' => $this->machine->id,
            'nama_lokasi' => $this->machine->nama_lokasi,
            'kapasitas' => $this->machine->kapasitas_terkini,
            'icon' => 'warning',
            'warning' => true,
        ];
    }
}

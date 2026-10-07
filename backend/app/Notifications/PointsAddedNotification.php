<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PointsAddedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $jumlahBotol,
        public readonly int $poinDiperoleh,
        public readonly string $namaLokasi,
        public readonly ?int $transactionId = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'points_added',
            'title' => 'Poin Bertambah',
            'description' => "+{$this->poinDiperoleh} Poin dari Mesin {$this->namaLokasi}",
            'jumlah_botol' => $this->jumlahBotol,
            'poin' => $this->poinDiperoleh,
            'nama_lokasi' => $this->namaLokasi,
            'transaction_id' => $this->transactionId,
            'icon' => 'add',
            'warning' => false,
        ];
    }
}

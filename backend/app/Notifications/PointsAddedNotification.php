<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
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
        return ['database', FcmChannel::class];
    }

    /** Payload push notification (tampil di lock screen). */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'Botol berhasil disetor! ♻️',
            'body' => "+{$this->poinDiperoleh} poin dari {$this->jumlahBotol} botol di {$this->namaLokasi}",
            'data' => [
                'type' => 'points_added',
                'poin' => (string) $this->poinDiperoleh,
                'jumlah_botol' => (string) $this->jumlahBotol,
                'transaction_id' => (string) ($this->transactionId ?? ''),
            ],
        ];
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

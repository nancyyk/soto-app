<?php

namespace App\Notifications;

use App\Models\Machine;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi push + database saat tempat sampah (mesin RVM) penuh.
 * Trigger: NotificationService::checkAndNotifyMachineCapacityTransition()
 *          saat kapasitas >= threshold (default 80%).
 * Penerima: Admin + User normal.
 */
class MachineFullNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Machine $machine
    ) {}

    /**
     * Channel: database + FCM push notification.
     */
    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    /**
     * Payload push notification FCM untuk lock screen Android.
     */
    public function toFcm(object $notifiable): array
    {
        $lokasi    = $this->machine->nama_lokasi;
        $kapasitas = $this->machine->kapasitas_terkini;

        return [
            'title' => '⚠️ Tempat Sampah Penuh!',
            'body'  => "Mesin SOTO di {$lokasi} sudah {$kapasitas}% penuh. Segera kosongkan.",
            'data'  => [
                'type'       => 'machine_full',
                'machine_id' => (string) $this->machine->id,
                'lokasi'     => $lokasi,
                'kapasitas'  => (string) $kapasitas,
            ],
        ];
    }

    /**
     * Payload untuk database (riwayat notifikasi di app).
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'machine_full',
            'title'       => 'Mesin Penuh',
            'description' => "Mesin SOTO di {$this->machine->nama_lokasi} sedang penuh ({$this->machine->kapasitas_terkini}%)",
            'machine_id'  => $this->machine->id,
            'nama_lokasi' => $this->machine->nama_lokasi,
            'kapasitas'   => $this->machine->kapasitas_terkini,
            'icon'        => 'warning',
            'warning'     => true,
        ];
    }
}

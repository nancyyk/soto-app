<?php

namespace App\Notifications;

use App\Models\RewardRedemption;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi push + database saat user berhasil menukar reward.
 * Trigger: RewardController::redeem() setelah DB commit berhasil.
 */
class RewardSuccessNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly RewardRedemption $redemption
    ) {}

    /**
     * Channel yang digunakan: database (riwayat) + FCM (push notification).
     */
    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    /**
     * Payload push notification FCM untuk lock screen Android.
     * Dipanggil oleh FcmChannel::send().
     */
    public function toFcm(object $notifiable): array
    {
        $rewardName = $this->redemption->reward?->nama ?? 'Reward';
        $poinDipakai = $this->redemption->poin;

        // Hitung sisa poin dari user (saldo setelah penukaran sudah dikurangi)
        $sisaPoin = $notifiable->saldo_poin ?? 0;

        return [
            'title' => 'Penukaran Reward Berhasil 🎁',
            'body'  => "Kamu berhasil menukar {$rewardName} ({$poinDipakai} poin). Sisa poin: {$sisaPoin}",
            'data'  => [
                'type'         => 'reward_success',
                'reward_name'  => $rewardName,
                'poin_dipakai' => (string) $poinDipakai,
                'sisa_poin'    => (string) $sisaPoin,
                'redemption_id'=> (string) $this->redemption->id,
                'reward_id'    => (string) $this->redemption->reward_id,
            ],
        ];
    }

    /**
     * Payload untuk disimpan ke database notifications (riwayat di app).
     */
    public function toArray(object $notifiable): array
    {
        $rewardName = $this->redemption->reward?->nama ?? 'Reward';

        return [
            'type'          => 'reward_success',
            'title'         => 'Penukaran Reward Berhasil',
            'description'   => "Kamu berhasil menukar reward {$rewardName}",
            'redemption_id' => $this->redemption->id,
            'reward_id'     => $this->redemption->reward_id,
            'poin'          => $this->redemption->poin,
            'status'        => $this->redemption->status,
            'icon'          => 'check',
            'warning'       => false,
        ];
    }
}

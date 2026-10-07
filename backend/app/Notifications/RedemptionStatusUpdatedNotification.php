<?php

namespace App\Notifications;

use App\Models\RewardRedemption;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RedemptionStatusUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly RewardRedemption $redemption
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $rewardName = $this->redemption->reward?->nama ?? 'Reward';
        $statusLabel = ucfirst($this->redemption->status);

        $desc = "Status penukaran {$rewardName} telah diubah menjadi {$statusLabel}.";
        if ($this->redemption->nomor_resi) {
            $desc .= " No Resi: {$this->redemption->nomor_resi} ({$this->redemption->ekspedisi})";
        }

        return [
            'title' => 'Status Penukaran Diperbarui',
            'description' => $desc,
            'redemption_id' => $this->redemption->id,
            'reward_id' => $this->redemption->reward_id,
            'status' => $this->redemption->status,
            'ekspedisi' => $this->redemption->ekspedisi,
            'nomor_resi' => $this->redemption->nomor_resi,
            'icon' => 'check',
            'warning' => false,
        ];
    }
}

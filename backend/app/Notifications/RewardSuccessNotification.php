<?php

namespace App\Notifications;

use App\Models\RewardRedemption;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RewardSuccessNotification extends Notification
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

        return [
            'type' => 'reward_success',
            'title' => 'Reward Berhasil',
            'description' => "Kamu berhasil menukar reward {$rewardName}",
            'redemption_id' => $this->redemption->id,
            'reward_id' => $this->redemption->reward_id,
            'poin' => $this->redemption->poin,
            'status' => $this->redemption->status,
            'icon' => 'check',
            'warning' => false,
        ];
    }
}

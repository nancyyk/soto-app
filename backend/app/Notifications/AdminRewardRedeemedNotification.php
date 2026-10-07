<?php

namespace App\Notifications;

use App\Models\RewardRedemption;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdminRewardRedeemedNotification extends Notification
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
        $userName = $this->redemption->user?->nama ?? ('Pengguna #' . $this->redemption->user_id);
        $rewardName = $this->redemption->reward?->nama ?? ('Reward #' . $this->redemption->reward_id);
        $points = $this->redemption->poin;

        return [
            'type' => 'reward_redeemed',
            'title' => 'Penukaran Reward Baru',
            'description' => "{$userName} menukar reward {$rewardName} sebesar {$points} poin",
            'redemption_id' => $this->redemption->id,
            'user_id' => $this->redemption->user_id,
            'reward_id' => $this->redemption->reward_id,
            'poin' => $points,
            'status' => $this->redemption->status,
            'icon' => 'gift',
            'warning' => false,
        ];
    }
}

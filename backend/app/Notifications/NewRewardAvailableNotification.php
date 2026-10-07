<?php

namespace App\Notifications;

use App\Models\Reward;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewRewardAvailableNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Reward $reward
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Reward Baru',
            'description' => "Reward baru '{$this->reward->nama}' kini tersedia! Tukarkan {$this->reward->poin} poin milikmu.",
            'reward_id' => $this->reward->id,
            'nama_reward' => $this->reward->nama,
            'poin' => $this->reward->poin,
            'icon' => 'gift',
            'warning' => false,
        ];
    }
}

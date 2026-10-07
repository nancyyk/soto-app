<?php

namespace App\Notifications;

use App\Models\Reward;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StockWarningNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Reward $reward,
        public readonly string $type // 'low' or 'empty'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $isLow = $this->type === 'low';

        return [
            'title' => $isLow ? 'Stok Hadiah Menipis' : 'Stok Hadiah Habis',
            'description' => $isLow
                ? "Stok reward {$this->reward->nama} tersisa {$this->reward->stok}"
                : "Stok reward {$this->reward->nama} telah habis!",
            'reward_id' => $this->reward->id,
            'nama_reward' => $this->reward->nama,
            'stok' => $this->reward->stok,
            'type' => $this->type,
            'icon' => 'warning',
            'warning' => true,
        ];
    }
}

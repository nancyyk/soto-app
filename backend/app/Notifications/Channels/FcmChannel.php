<?php

namespace App\Notifications\Channels;

use App\Services\FcmService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class FcmChannel
{
    public function __construct(private readonly FcmService $fcm) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toFcm')) {
            return;
        }

        $message = $notification->toFcm($notifiable);

        try {
            $this->fcm->sendToUser(
                $notifiable->id,
                $message['title'],
                $message['body'],
                $message['data'] ?? [],
            );
        } catch (\Throwable $e) {
            // Gagal push tidak boleh menggagalkan transaksi
            Log::error('FCM channel error: '.$e->getMessage());
        }
    }
}

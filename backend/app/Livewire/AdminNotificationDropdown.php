<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AdminNotificationDropdown extends Component
{
    public bool $isOpen = false;

    public function toggleDropdown(): void
    {
        $this->isOpen = ! $this->isOpen;
    }

    public function markAsRead(string $notificationId): void
    {
        $user = Auth::user();
        if ($user) {
            $notification = $user->notifications()->find($notificationId);
            if ($notification) {
                $notification->markAsRead();
            }
        }
    }

    public function markAllAsRead(): void
    {
        $user = Auth::user();
        if ($user) {
            $user->unreadNotifications->markAsRead();
        }
    }

    public function getNotificationUrl(array $data): string
    {
        $type = $data['type'] ?? '';

        if ($type === 'reward_redeemed' || isset($data['redemption_id']) || isset($data['reward_id'])) {
            return route('reward.index');
        }

        if ($type === 'machine_full' || isset($data['machine_id'])) {
            $machineId = $data['machine_id'] ?? null;
            if ($machineId) {
                return route('monitoring.show', $machineId);
            }
            return route('monitoring.index');
        }

        if ($type === 'transaction' || isset($data['transaction_id'])) {
            return route('transaksi.index');
        }

        return route('dashboard');
    }

    public function openNotification(string $notificationId)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        $notification = $user->notifications()->find($notificationId);
        if ($notification) {
            $notification->markAsRead();
            $url = $this->getNotificationUrl($notification->data ?? []);
            return redirect()->to($url);
        }

        return redirect()->route('dashboard');
    }

    public function render()
    {
        $user = Auth::user();
        $unreadCount = $user ? $user->unreadNotifications->count() : 0;
        $notifications = $user ? $user->notifications()->take(10)->get() : collect();

        return view('livewire.admin-notification-dropdown', [
            'unreadCount' => $unreadCount,
            'notifications' => $notifications,
        ]);
    }
}

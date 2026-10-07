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

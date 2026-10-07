<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Machine;
use App\Models\Reward;
use App\Models\User;
use App\Notifications\MachineFullNotification;
use App\Notifications\NewRewardAvailableNotification;
use App\Notifications\StockWarningNotification;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    /**
     * Stock transition check and admin notification dispatch.
     *
     * Rules:
     * - stock > 2 -> stock <= 2 (and > 0): send low-stock notification once
     * - stock > 0 -> stock == 0: send out-of-stock notification once
     * - no duplicates when saving same stock level.
     */
    public static function checkAndNotifyRewardStockTransition(Reward $reward, int $oldStock): void
    {
        $newStock = (int) $reward->stok;

        // Admin notifications must only be sent to users whose role is Admin
        $admins = User::where('role', UserRole::Admin)->get();
        if ($admins->isEmpty()) {
            return;
        }

        // Transition 1: stock > 2 -> stock <= 2 and stock > 0
        if ($oldStock > 2 && $newStock <= 2 && $newStock > 0) {
            Notification::send($admins, new StockWarningNotification($reward, 'low'));
        }

        // Transition 2: stock > 0 -> stock == 0
        if ($oldStock > 0 && $newStock == 0) {
            Notification::send($admins, new StockWarningNotification($reward, 'empty'));
        }
    }

    /**
     * Machine full capacity transition check.
     *
     * Rules:
     * - normal -> full: send notification to BOTH relevant Normal Users and Admins
     * - full -> full: do not send notification
     * - full -> normal: reset full state (no notification, next time full will send again)
     * - normal -> full again: send notification again
     */
    public static function checkAndNotifyMachineCapacityTransition(Machine $machine, int $oldCapacity): void
    {
        $newCapacity = (int) $machine->kapasitas_terkini;
        $threshold = (int) ($machine->threshold_capacity ?? 80);

        $wasFull = $oldCapacity >= $threshold;
        $isFull = $newCapacity >= $threshold;

        if (! $wasFull && $isFull) {
            // 1. Notify Admins
            $admins = User::where('role', UserRole::Admin)->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new MachineFullNotification($machine));
            }

            // 2. Notify relevant Normal Users
            $users = User::where('role', UserRole::User)->get();
            if ($users->isNotEmpty()) {
                Notification::send($users, new MachineFullNotification($machine));
            }
        }
    }

    /**
     * Notify normal users about new reward available.
     *
     * Rules:
     * - Active normal users only (role = 'user').
     * - Do NOT send to Admin users.
     */
    public static function notifyNewRewardAvailable(Reward $reward): void
    {
        if (! $reward->is_active) {
            return;
        }

        $normalUsers = User::where('role', UserRole::User)->get();
        if ($normalUsers->isNotEmpty()) {
            Notification::send($normalUsers, new NewRewardAvailableNotification($reward));
        }
    }
}

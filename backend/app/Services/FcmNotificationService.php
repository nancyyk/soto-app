<?php

namespace App\Services;

use App\Models\Machine;
use App\Models\User;
use App\Notifications\MachineFullNotification;
use App\Notifications\PointsAddedNotification;
use App\Notifications\RewardSuccessNotification;
use App\Models\RewardRedemption;
use Illuminate\Support\Facades\Notification;
use App\Enums\UserRole;

/**
 * Facade sederhana untuk mengirim tiga jenis push notification FCM.
 *
 * Metode ini tidak melakukan query DB atau logika bisnis baru —
 * hanya membungkus Notification::send() dengan payload yang tepat.
 * Gunakan ini dari controller atau job setelah operasi DB selesai.
 */
class FcmNotificationService
{
    /**
     * Kirim notifikasi "Setoran Berhasil" ke user.
     *
     * Dipanggil oleh: ProcessTransactionJob::handle() setelah transaksi DB sukses.
     * Sudah diimplementasikan via PointsAddedNotification.
     *
     * @param  User   $user         User pemilik RFID
     * @param  int    $jumlahBotol  Jumlah botol yang disetor
     * @param  int    $poin         Poin yang diperoleh
     * @param  string $namaLokasi   Nama mesin/lokasi
     * @param  int|null $transactionId ID transaksi untuk deduplication
     */
    public function sendSetoranBerhasil(
        User $user,
        int $jumlahBotol,
        int $poin,
        string $namaLokasi,
        ?int $transactionId = null,
    ): void {
        $user->notify(new PointsAddedNotification(
            jumlahBotol:   $jumlahBotol,
            poinDiperoleh: $poin,
            namaLokasi:    $namaLokasi,
            transactionId: $transactionId,
        ));
    }

    /**
     * Kirim notifikasi "Reward Berhasil Ditukar" ke user.
     *
     * Dipanggil oleh: RewardController::redeem() setelah DB commit.
     *
     * @param  User             $user       User yang melakukan penukaran
     * @param  RewardRedemption $redemption Data penukaran (sudah load 'reward')
     */
    public function sendRewardBerhasil(User $user, RewardRedemption $redemption): void
    {
        $user->notify(new RewardSuccessNotification($redemption));
    }

    /**
     * Kirim notifikasi "Tempat Sampah Penuh" ke semua admin dan user normal.
     *
     * Dipanggil oleh: NotificationService::checkAndNotifyMachineCapacityTransition()
     * saat terjadi transisi normal → penuh.
     *
     * @param  Machine $machine Mesin yang penuh
     */
    public function sendSampahPenuh(Machine $machine): void
    {
        // Kirim ke Admin
        $admins = User::where('role', UserRole::Admin)->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new MachineFullNotification($machine));
        }

        // Kirim ke User normal
        $users = User::where('role', UserRole::User)->get();
        if ($users->isNotEmpty()) {
            Notification::send($users, new MachineFullNotification($machine));
        }
    }
}

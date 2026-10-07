<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use App\Notifications\AdminRewardRedeemedNotification;
use App\Notifications\RedemptionStatusUpdatedNotification;
use App\Notifications\RewardSuccessNotification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class RewardController extends Controller
{
    public function index()
    {
        return response()->json(
            Reward::where('is_active', true)->get()
        );
    }

    public function redeem(Request $request)
    {
        $validated = $request->validate([
            'reward_id' => 'required|exists:rewards,id',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $reward = Reward::findOrFail($validated['reward_id']);

        $shippingAddress = null;
        $recipientNumber = null;

        if ($reward->kategori === 'Merchandise') {
            $request->validate([
                'shipping_address' => 'required|string',
            ]);
            $shippingAddress = $request->shipping_address;
        } elseif ($reward->kategori === 'E-Wallet' || $reward->kategori === 'Pulsa') {
            $request->validate([
                'recipient_number' => 'required|string',
            ]);
            $recipientNumber = $request->recipient_number;
        }

        if ($reward->stok < 1) {
            return response()->json([
                'message' => 'Stok hadiah habis'
            ], 400);
        }

        if ($user->saldo_poin < $reward->poin) {
            return response()->json([
                'message' => 'Poin tidak cukup'
            ], 400);
        }

        $oldStock = (int) $reward->stok;

        DB::beginTransaction();

        try {
            $user->decrement('saldo_poin', $reward->poin);
            $reward->decrement('stok');

            $redemption = RewardRedemption::create([
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'poin' => $reward->poin,
                'status' => 'proses',
                'shipping_address' => $shippingAddress,
                'recipient_number' => $recipientNumber,
                'ekspedisi' => null,
                'nomor_resi' => null,
            ]);

            DB::commit();

            // 1. Dispatch Reward Success Notification to User
            $user->notify(new RewardSuccessNotification($redemption));

            // 2. Dispatch Reward Redeemed Notification to Admins
            $admins = User::where('role', UserRole::Admin)->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new AdminRewardRedeemedNotification($redemption));
            }

            // 3. Stock transition notification for admins
            NotificationService::checkAndNotifyRewardStockTransition($reward->fresh(), $oldStock);

            return response()->json([
                'message' => 'Berhasil menukar reward',
                'data' => $redemption
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Terjadi kesalahan sistem'
            ], 500);
        }
    }

    public function myRedemptions(Request $request)
    {
        $data = RewardRedemption::with('reward')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json($data);
    }

    public function adminIndex()
    {
        return response()->json(
            Reward::all()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'required|string|max:50|unique:rewards,kode',
            'nama' => 'required|string|max:150',
            'deskripsi' => 'nullable|string',
            'poin' => 'required|integer|min:0',
            'stok' => 'required|integer|min:0',
            'kategori' => 'required|string',
            'gambar' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $reward = Reward::create($validated);

        // Notify normal users of new reward
        NotificationService::notifyNewRewardAvailable($reward);

        // Check low stock if created with low stock
        if ($reward->stok <= 2) {
            NotificationService::checkAndNotifyRewardStockTransition($reward, 10);
        }

        return response()->json([
            'message' => 'Reward ditambahkan',
            'data' => $reward
        ]);
    }

    public function update(Request $request, Reward $reward)
    {
        $oldStock = (int) $reward->stok;

        $validated = $request->validate([
            'kode' => 'sometimes|required|string|max:50|unique:rewards,kode,' . $reward->id,
            'nama' => 'sometimes|required|string|max:150',
            'deskripsi' => 'nullable|string',
            'poin' => 'sometimes|required|integer|min:0',
            'stok' => 'sometimes|required|integer|min:0',
            'kategori' => 'sometimes|required|string',
            'gambar' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $reward->update($validated);

        NotificationService::checkAndNotifyRewardStockTransition($reward->fresh(), $oldStock);

        return response()->json([
            'message' => 'Reward diupdate',
            'data' => $reward
        ]);
    }

    public function adminRedemptions()
    {
        return response()->json(
            RewardRedemption::with(['user', 'reward'])
                ->latest()
                ->get()
        );
    }

    public function updateRedemptionStatus(
        Request $request,
        RewardRedemption $redemption
    ) {
        $validated = $request->validate([
            'status' => 'required|in:proses,pengiriman,selesai',
            'ekspedisi' => 'nullable|string',
            'nomor_resi' => 'nullable|string',
        ]);

        $redemption->update($validated);

        if ($redemption->user) {
            $redemption->user->notify(new RedemptionStatusUpdatedNotification($redemption));
        }

        return response()->json([
            'message' => 'Status diupdate',
            'data' => $redemption
        ]);
    }
}

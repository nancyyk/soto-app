<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Reward;
use App\Models\RewardRedemption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RewardController extends Controller
{
    // ==========================================
    // USER ENDPOINTS
    // ==========================================
    public function index()
    {
        return response()->json(Reward::where('is_active', true)->get());
    }

    public function redeem(Request $request)
    {
        $request->validate([
            'reward_id' => 'required|exists:rewards,id',
            'shipping_address' => 'required|string',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $reward = Reward::findOrFail($request->reward_id);

        if ($reward->stock < 1) {
            return response()->json(['message' => 'Stok hadiah habis'], 400);
        }

        if ($user->saldo_poin < $reward->points_required) {
            return response()->json(['message' => 'Poin tidak cukup'], 400);
        }

        DB::beginTransaction();
        try {
            $user->decrement('saldo_poin', $reward->points_required);
            $reward->decrement('stock');

            $redemption = RewardRedemption::create([
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'points_used' => $reward->points_required,
                'status' => 'proses',
                'shipping_address' => $request->shipping_address,
            ]);

            DB::commit();
            return response()->json(['message' => 'Berhasil menukar reward', 'data' => $redemption]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan sistem'], 500);
        }
    }

    public function myRedemptions(Request $request)
    {
        $data = RewardRedemption::with('reward')->where('user_id', $request->user()->id)->latest()->get();
        return response()->json($data);
    }

    // ==========================================
    // ADMIN ENDPOINTS
    // ==========================================
    public function adminIndex()
    {
        return response()->json(Reward::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'points_required' => 'required|integer',
            'stock' => 'required|integer',
            'image_url' => 'nullable|string',
        ]);
        $reward = Reward::create($validated);
        return response()->json(['message' => 'Reward ditambahkan', 'data' => $reward]);
    }

    public function update(Request $request, Reward $reward)
    {
        $reward->update($request->all());
        return response()->json(['message' => 'Reward diupdate', 'data' => $reward]);
    }

    public function adminRedemptions()
    {
        return response()->json(RewardRedemption::with(['user', 'reward'])->latest()->get());
    }

    public function updateRedemptionStatus(Request $request, RewardRedemption $redemption)
    {
        $request->validate([
            'status' => 'required|in:proses,pengiriman,selesai',
            'tracking_number' => 'nullable|string'
        ]);
        $redemption->update($request->only(['status', 'tracking_number']));
        return response()->json(['message' => 'Status diupdate', 'data' => $redemption]);
    }
}
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Pengguna & Poin
 */
class UserController extends Controller
{
    /**
     * Saldo poin dan histori transaksi pengguna.
     *
     * @urlParam id integer required ID pengguna. Example: 1
     */
    public function points(Request $request, User $user): JsonResponse
    {
        // Only the user themselves or an admin can see this
        if ($request->user()->id !== $user->id && ! $request->user()->isAdmin()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $transactions = $user->transactions()
            ->with('machine:id,nama_lokasi')
            ->latest('created_at')
            ->paginate(20);

        return response()->json([
            'user' => ['id' => $user->id, 'nama' => $user->nama, 'email' => $user->email],
            'saldo_poin' => $user->saldo_poin,
            'transactions' => $transactions,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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
            'total_botol' => (int) $user->transactions()->sum('jumlah_botol'),
            'transactions' => $transactions,
        ]);
    }

    /**
     * Profil pengguna yang sedang login.
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id'    => $user->id,
                'nama'  => $user->nama,
                'email' => $user->email,
                'no_hp' => $user->no_hp ?? null,
                'role'  => $user->role,
                'saldo_poin' => $user->saldo_poin ?? 0,
            ],
        ]);
    }

    /**
     * Update profil pengguna (nama & nomor HP).
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'nama'  => 'required|string|max:255',
            'no_hp' => 'nullable|string|max:20',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => [
                'id'    => $user->id,
                'nama'  => $user->nama,
                'email' => $user->email,
                'no_hp' => $user->no_hp ?? null,
                'role'  => $user->role,
            ],
        ]);
    }

    /**
     * Ganti password pengguna yang sedang login.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'old_password'     => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);

        if (! Hash::check($request->old_password, $user->password)) {
            throw ValidationException::withMessages([
                'old_password' => ['Password lama tidak sesuai.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'message' => 'Password berhasil diperbarui.',
        ]);
    }
}

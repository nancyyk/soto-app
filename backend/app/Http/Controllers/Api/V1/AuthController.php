<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group Autentikasi
 */
class AuthController extends Controller
{
    /**
     * Login dan dapatkan token Sanctum.
     *
     * @unauthenticated
     * @bodyParam email string required Email pengguna. Example: admin@soto.test
     * @bodyParam password string required Password. Example: password
     * @response 200 {"token": "1|abc...", "user": {"id": 1, "nama": "Admin", "role": "admin"}}
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Kredensial tidak valid'], 401);
        }

        $user  = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'   => $user->id,
                'nama' => $user->nama,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * Logout dan cabut token aktif.
     *
     * @response 200 {"message": "Logged out"}
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }
}

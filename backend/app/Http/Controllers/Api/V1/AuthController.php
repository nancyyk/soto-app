<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group Autentikasi
 */
class AuthController extends Controller
{
    /**
     * Register pengguna baru dan dapatkan token Sanctum.
     *
     * @unauthenticated
     *
     * @bodyParam nama string required Nama pengguna. Example: Fikri
     * @bodyParam email string required Email pengguna. Example: fikri@soto.test
     * @bodyParam password string required Password. Example: password123
     *
     * @response 201 {"token": "1|abc...", "user": {"id": 1, "nama": "Fikri", "role": "user"}}
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'nama' => $validated['nama'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'user',
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'nama' => $user->nama,
                'role' => $user->role,
                'rfid_uid' => $user->rfid_uid,
            ],
        ], 201);
    }

    /**
     * Login dan dapatkan token Sanctum.
     *
     * @unauthenticated
     *
     * @bodyParam email string required Email pengguna. Example: admin@soto.test
     * @bodyParam password string required Password. Example: password
     *
     * @response 200 {"token": "1|abc...", "user": {"id": 1, "nama": "Admin", "role": "admin"}}
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return response()->json(['message' => 'Kredensial tidak valid'], 401);
        }

        /** @var User  */
        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'nama' => $user->nama,
                'role' => $user->role,
                'rfid_uid' => $user->rfid_uid,
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


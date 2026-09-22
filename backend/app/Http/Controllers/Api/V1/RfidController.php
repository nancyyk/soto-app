<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RfidScanRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RfidController extends Controller
{
    public function startScan(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->rfid_uid) {
            return response()->json(['message' => 'User sudah memiliki kartu RFID'], 400);
        }

        $deviceId = $request->input('device_id', 'esp32-soto-01');

        RfidScanRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);

        $scanRequest = RfidScanRequest::create([
            'user_id' => $user->id,
            'device_id' => $deviceId,
            'status' => 'pending',
            'expires_at' => now()->addSeconds(30),
        ]);

        return response()->json([
            'message' => 'Silakan tap kartu pada alat.',
            'request_id' => $scanRequest->id
        ]);
    }

    public function checkStatus(Request $request)
    {
        $user = Auth::user();

        $scanRequest = RfidScanRequest::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->first();

        if (!$scanRequest) {
            return response()->json(['status' => 'not_found'], 404);
        }

        if ($scanRequest->status === 'pending' && now()->gt($scanRequest->expires_at)) {
            $scanRequest->update(['status' => 'expired']);
        }

        return response()->json([
            'status' => $scanRequest->status,
            'rfid_uid' => $scanRequest->rfid_uid,
        ]);
    }

    public function receiveUid(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
            'uid' => 'required|string'
        ]);

        $uid = strtoupper(trim($request->uid));

        $isUsed = User::where('rfid_uid', $uid)->exists();
        if ($isUsed) {
            return response()->json(['message' => 'Kartu sudah terdaftar ke akun lain'], 400);
        }

        $scanRequest = RfidScanRequest::where('device_id', $request->device_id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'asc')
            ->first();

        if (!$scanRequest) {
            return response()->json(['message' => 'Tidak ada request scan yang aktif'], 404);
        }

        $user = User::find($scanRequest->user_id);
        $user->rfid_uid = $uid;
        $user->rfid_registered_at = now();
        $user->save();

        $scanRequest->status = 'completed';
        $scanRequest->rfid_uid = $uid;
        $scanRequest->save();

        return response()->json(['message' => 'Pairing berhasil']);
    }

    public function unlink(Request $request)
    {
        $user = Auth::user();
        $user->rfid_uid = null;
        $user->rfid_registered_at = null;
        $user->save();

        return response()->json(['message' => 'Kartu berhasil dihapus dari akun']);
    }
}
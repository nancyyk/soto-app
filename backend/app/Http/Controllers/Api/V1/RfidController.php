<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RfidScanRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Exceptions\MqttClientException;
use PhpMqtt\Client\MqttClient;

class RfidController extends Controller
{
    /**
     * POST /api/v1/rfid/scan
     *
     * Flutter memanggil endpoint ini saat user tap "Hubungkan RFID".
     * Setelah membuat RfidScanRequest pending, controller mempublish
     * perintah MQTT ke ESP32 agar ESP32 mulai mode scanning.
     *
     * Jika publish MQTT gagal (misal: server MQTT down), response ke Flutter
     * tetap sukses — ESP32 akan di-poll ulang saat user retry.
     */
    public function startScan(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        // Jika user sudah punya kartu RFID, tolak request
        if ($user->rfid_uid) {
            return response()->json(['message' => 'User sudah memiliki kartu RFID'], 400);
        }

        $deviceId = $request->input('device_id', 'esp32-soto-01');

        // Expire semua request pending user ini sebelum buat yang baru
        RfidScanRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);

        // Buat scan request baru dengan expiry 30 detik
        $scanRequest = RfidScanRequest::create([
            'user_id'    => $user->id,
            'device_id'  => $deviceId,
            'status'     => 'pending',
            'expires_at' => now()->addSeconds(30),
        ]);

        Log::info('[RFID] Scan request dibuat', [
            'request_id' => $scanRequest->id,
            'user_id'    => $user->id,
            'device_id'  => $deviceId,
            'expires_at' => $scanRequest->expires_at,
        ]);

        // --- Publish MQTT command ke ESP32 ---
        $this->publishScanCommand($deviceId, $scanRequest->id);

        return response()->json([
            'message'    => 'Silakan tap kartu pada alat.',
            'request_id' => $scanRequest->id,
        ]);
    }

    /**
     * GET /api/v1/rfid/status
     *
     * Flutter polling endpoint ini setiap 2 detik untuk mengetahui
     * apakah kartu sudah berhasil dipasangkan.
     */
    public function checkStatus(Request $request)
    {
        $user = Auth::user();

        $scanRequest = RfidScanRequest::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->first();

        if (! $scanRequest) {
            return response()->json(['status' => 'not_found'], 404);
        }

        // Auto-expire jika sudah lewat waktu dan masih pending
        if ($scanRequest->status === 'pending' && now()->gt($scanRequest->expires_at)) {
            $scanRequest->update(['status' => 'expired']);
        }

        return response()->json([
            'status'   => $scanRequest->status,
            'rfid_uid' => $scanRequest->rfid_uid,
        ]);
    }

    /**
     * POST /api/v1/rfid/uid  (middleware: device.key)
     *
     * Endpoint lama dari ESP32 via HTTP — dipertahankan sebagai fallback.
     * Dalam arsitektur MQTT baru, ESP32 langsung publish ke HiveMQ dan
     * MqttRfidListener yang memproses. Endpoint ini tidak dihapus agar
     * kompatibilitas backward tetap terjaga.
     */
    public function receiveUid(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
            'uid'       => 'required|string',
        ]);

        $uid = strtoupper(trim($request->uid));

        // Cek apakah UID sudah dipakai user lain
        $isUsed = User::where('rfid_uid', $uid)->exists();
        if ($isUsed) {
            return response()->json(['message' => 'Kartu sudah terdaftar ke akun lain'], 400);
        }

        // Cari scan request pending yang masih aktif untuk device ini
        $scanRequest = RfidScanRequest::where('device_id', $request->device_id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'asc')
            ->first();

        if (! $scanRequest) {
            return response()->json(['message' => 'Tidak ada request scan yang aktif'], 404);
        }

        // Simpan RFID ke user
        $user = User::find($scanRequest->user_id);
        $user->rfid_uid           = $uid;
        $user->rfid_registered_at = now();
        $user->save();

        // Update status scan request
        $scanRequest->status   = 'completed';
        $scanRequest->rfid_uid = $uid;
        $scanRequest->save();

        Log::info('[RFID] Pairing sukses via HTTP (fallback)', [
            'uid'       => $uid,
            'user_id'   => $user->id,
            'device_id' => $request->device_id,
        ]);

        return response()->json(['message' => 'Pairing berhasil']);
    }

    /**
     * DELETE /api/v1/rfid/unlink
     *
     * Flutter memanggil endpoint ini untuk melepas kartu RFID dari akun.
     */
    public function unlink(Request $request)
    {
        $user = Auth::user();
        $user->rfid_uid           = null;
        $user->rfid_registered_at = null;
        $user->save();

        Log::info('[RFID] Kartu di-unlink', ['user_id' => $user->id]);

        return response()->json(['message' => 'Kartu berhasil dihapus dari akun']);
    }

    // =========================================================================
    // PRIVATE HELPER
    // =========================================================================

    /**
     * Publish perintah scan ke ESP32 melalui HiveMQ Cloud.
     *
     * Topik  : soto/device/{device_id}/command
     * Payload: {"action":"scan","request_id":<id>,"expires_in":30}
     *
     * Koneksi dibuka, publish satu pesan, lalu langsung tutup (fire-and-forget).
     * Jika gagal, error dicatat ke log tapi TIDAK melempar exception ke caller
     * agar response ke Flutter tetap sukses.
     *
     * @param  string $deviceId   ID perangkat ESP32
     * @param  int    $requestId  ID dari RfidScanRequest yang baru dibuat
     */
    private function publishScanCommand(string $deviceId, int $requestId): void
    {
        $host     = config('mqtt.host');
        $port     = config('mqtt.port');
        $username = config('mqtt.username');
        $password = config('mqtt.password');
        $clientId = config('mqtt.publisher_client_id');
        $useTls   = config('mqtt.tls');

        $topic   = "soto/device/{$deviceId}/command";
        $payload = json_encode([
            'action'     => 'scan',
            'request_id' => $requestId,
            'expires_in' => 30,
        ]);

        try {
            // Bangun connection settings
            $settings = (new ConnectionSettings)
                ->setUsername($username)
                ->setPassword($password)
                ->setKeepAliveInterval(config('mqtt.keep_alive', 60))
                ->setConnectTimeout(config('mqtt.connect_timeout', 30));

            if ($useTls) {
                $tlsOpts  = config('mqtt.tls_settings', []);
                $settings = $settings
                    ->setUseTls(true)
                    ->setTlsVerifyPeer($tlsOpts['verify_peer'] ?? true)
                    ->setTlsVerifyPeerName($tlsOpts['verify_peer_name'] ?? true);
            }

            // Buat koneksi MQTT sementara (clean session)
            $mqtt = new MqttClient($host, $port, $clientId);
            $mqtt->connect($settings, true);

            // Publish perintah scan ke ESP32 (QoS 1 agar terjamin terkirim)
            $mqtt->publish($topic, $payload, 1);

            $mqtt->disconnect();

            Log::info('[RFID] MQTT command dikirim ke ESP32', [
                'topic'      => $topic,
                'payload'    => $payload,
                'request_id' => $requestId,
                'device_id'  => $deviceId,
            ]);
        } catch (MqttClientException $e) {
            // Catat error tapi jangan gagalkan response ke Flutter
            Log::error('[RFID] Gagal publish MQTT command ke ESP32', [
                'topic'   => $topic,
                'error'   => $e->getMessage(),
                'device'  => $deviceId,
            ]);
        } catch (\Throwable $e) {
            Log::error('[RFID] Error tidak terduga saat publish MQTT', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
<?php

namespace App\Console\Commands;

use App\Models\RfidScanRequest;
use App\Models\User;
use App\Models\Machine;
use App\Models\Device;
use App\Models\LogCapacity;
use App\Events\NodeTelemetryUpdated;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Exceptions\MqttClientException;
use PhpMqtt\Client\MqttClient;

/**
 * MqttRfidListener — v2.0 FIXED
 *
 * Perubahan penting dari versi sebelumnya:
 *  - Callback dibungkus try-catch (tidak crash diam-diam)
 *  - Log RAW IN sebelum parsing (tahu pesan masuk)
 *  - handleSensorMessage() aman dari null tegangan_baterai
 *  - broadcast() dibungkus try-catch (Reverb down tidak crash)
 *  - QoS 0 untuk debugging (ganti ke 1 di production)
 *  - Update DB dan notifikasi dipisah try-catch
 */
class MqttRfidListener extends Command
{
    protected $signature = 'mqtt:listen-rfid';
    protected $description = 'Dengarkan UID RFID dari ESP32 via HiveMQ Cloud dan simpan ke database';

    private const TOPIC_UID = 'soto/device/+/uid';

    private ?MqttClient $mqtt = null;

    // ============================================================
    // ENTRY POINT
    // ============================================================
    public function handle(): int
    {
        $this->printBanner();

        while (true) {
            try {
                $this->mqtt = $this->createMqttClient();
                $this->connectMqtt();
                $this->subscribeAndLoop();
            } catch (MqttClientException $e) {
                $this->error('[MQTT] Koneksi terputus: ' . $e->getMessage());
                Log::error('[MqttRfidListener] Koneksi MQTT terputus', ['error' => $e->getMessage()]);
            } catch (\Throwable $e) {
                $this->error('[ERROR] ' . $e->getMessage());
                Log::error('[MqttRfidListener] Error tidak terduga', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $this->warn('[MQTT] Reconnect dalam 5 detik...');
            Log::info('[MqttRfidListener] Mencoba reconnect dalam 5 detik');
            sleep(5);
        }

        return self::SUCCESS;
    }

    // ============================================================
    // MQTT CONNECTION
    // ============================================================
    private function createMqttClient(): MqttClient
    {
        $host     = config('mqtt.host');
        $port     = config('mqtt.port');
        $clientId = config('mqtt.listener_client_id');

        $this->info("[MQTT] Membuat client: {$host}:{$port} (ID: {$clientId})");

        return new MqttClient($host, $port, $clientId);
    }

    private function connectMqtt(): void
    {
        $username = config('mqtt.username');
        $password = config('mqtt.password');
        $useTls   = config('mqtt.tls');

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

        $this->info('[MQTT] Menghubungkan ke HiveMQ Cloud...');
        $this->mqtt->connect($settings, true);

        $this->info('[MQTT] ✅ Terhubung ke HiveMQ Cloud');
        Log::info('[MqttRfidListener] Terhubung ke HiveMQ Cloud', [
            'host'      => config('mqtt.host'),
            'port'      => config('mqtt.port'),
            'client_id' => config('mqtt.listener_client_id'),
        ]);
    }

    // ============================================================
    // SUBSCRIBE & LOOP — dengan try-catch + raw log
    // ============================================================
    private function subscribeAndLoop(): void
    {
        $this->info('[MQTT] Subscribe ke topik UID, CHECK, TRANSACTION, dan SENSOR');
        $this->info('[MQTT] Menunggu data dari ESP32... (Ctrl+C untuk berhenti)');
        $this->line('');

        $subs = [
            'soto/device/+/uid'         => 'handleUidMessage',
            'soto/device/+/check'       => 'handleCheckMessage',
            'soto/device/+/transaction' => 'handleTransactionMessage',
            'soto/sensor/+'             => 'handleSensorMessage',
        ];

        foreach ($subs as $topic => $handler) {
            $this->mqtt->subscribe($topic, function (string $t, string $p) use ($handler) {
                // ▼▼▼ LOG RAW SEBELUM PROCESSING — bukti pesan sampai ▼▼▼
                $this->info("📥 [RAW IN] {$t} | " . substr($p, 0, 200));

                try {
                    $this->$handler($t, $p);
                } catch (\Throwable $e) {
                    $this->error("❌ [CALLBACK ERROR] {$handler}: " . $e->getMessage());
                    Log::error("[MqttRfidListener] Callback error", [
                        'handler' => $handler,
                        'topic'   => $t,
                        'payload' => $p,
                        'error'   => $e->getMessage(),
                        'file'    => $e->getFile(),
                        'line'    => $e->getLine(),
                        'trace'   => $e->getTraceAsString(),
                    ]);
                }
            }, 0); // QoS 0 untuk debugging — ubah ke 1 di production
            $this->line("  ✓ Subscribed: {$topic} → {$handler}");
        }

        $this->line('');
        $this->info('[MQTT] Loop blocking aktif — menunggu pesan...');
        $this->line('');

        $this->mqtt->loop(true);
        $this->mqtt->disconnect();
    }

    // ============================================================
    // MESSAGE HANDLERS
    // ============================================================

    private function handleCheckMessage(string $topic, string $payload): void
    {
        $this->line('');
        $this->line("📡 [CHECK MASUK] Topik: {$topic}");

        $data = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("❌ [CHECK] JSON invalid");
            return;
        }

        $deviceId = trim($data['device_id'] ?? '');
        $uid      = strtoupper(trim($data['uid'] ?? ''));

        if (empty($deviceId) || empty($uid)) {
            $this->error("❌ [CHECK] device_id atau uid kosong");
            return;
        }

        $user    = User::where('rfid_uid', $uid)->first();
        $isValid = $user ? true : false;

        $response = [
            'device_id' => $deviceId,
            'uid'       => $uid,
            'valid'     => $isValid,
            'nama'      => $user ? $user->nama : null,
        ];

        $topicOut = "soto/device/{$deviceId}/check_result";
        $this->mqtt->publish($topicOut, json_encode($response), 1);

        $this->info("✅ [CHECK HASIL] UID {$uid} -> " . ($isValid ? "VALID ({$user->nama})" : "TIDAK TERDAFTAR") . " | Dikirim ke {$topicOut}");
    }

    private function handleTransactionMessage(string $topic, string $payload): void
    {
        $this->line('');
        $this->line("[TRANSAKSI MASUK] Topik: {$topic}");

        $data = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("❌ [TRANSAKSI] JSON invalid");
            return;
        }

        $deviceId = trim($data['device_id'] ?? '');
        $uid      = strtoupper(trim($data['uid'] ?? ''));
        $botol    = (int)($data['jumlah_botol'] ?? 0);
        $poin     = (int)($data['total_poin'] ?? 0);

        if (empty($deviceId) || empty($uid)) {
            $this->error("❌ [TRANSAKSI] device_id atau uid kosong");
            return;
        }

        $user = User::where('rfid_uid', $uid)->first();
        if (!$user) {
            $this->warn("[TRANSAKSI GAGAL] UID {$uid} tidak ditemukan di database!");
            return;
        }

        try {
            $user->saldo_poin += $poin;
            $user->save();

            $machine = Machine::where('is_simulation', false)->first() ?? Machine::first();
            if (!$machine) {
                $this->error("Tidak ada mesin yang terdaftar di database.");
                return;
            }

            $transaction = \App\Models\Transaction::create([
                'user_id'        => $user->id,
                'machine_id'     => $machine->id,
                'jumlah_botol'   => $botol,
                'poin_diperoleh' => $poin,
                'created_at'     => now(),
            ]);

            $alreadyNotified = $user->notifications()
                ->where('type', \App\Notifications\PointsAddedNotification::class)
                ->whereJsonContains('data->transaction_id', $transaction->id)
                ->exists();

            if (!$alreadyNotified) {
                try {
                    $user->notify(new \App\Notifications\PointsAddedNotification(
                        jumlahBotol: $botol,
                        poinDiperoleh: $poin,
                        namaLokasi: $machine->nama_lokasi,
                        transactionId: $transaction->id,
                    ));
                } catch (\Throwable $e) {
                    $this->warn("⚠ [TRANSAKSI] Notifikasi gagal: " . $e->getMessage());
                }
            }

            $this->info("[TRANSAKSI SUKSES] User: {$user->nama} | Botol: {$botol} | Poin: {$poin} | Total Poin: {$user->saldo_poin}");
            Log::info('[MqttRfidListener] Transaksi berhasil disimpan', [
                'user_id'    => $user->id,
                'uid'        => $uid,
                'botol'      => $botol,
                'poin'       => $poin,
                'machine_id' => $machine->id,
            ]);
        } catch (\Throwable $e) {
            $this->error("[TRANSAKSI ERROR] " . $e->getMessage());
            Log::error('[MqttRfidListener] Gagal menyimpan transaksi', [
                'uid'   => $uid,
                'botol' => $botol,
                'poin'  => $poin,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ============================================================
    // SENSOR HANDLER — versi aman dari null & broadcast crash
    // ============================================================
    private function handleSensorMessage(string $topic, string $payload): void
    {
        $data = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("❌ [SENSOR] JSON invalid: {$payload}");
            return;
        }

        $deviceId  = trim($data['device_id'] ?? '');
        $kapasitas = (int)($data['kapasitas'] ?? 0);
        $tegangan  = isset($data['tegangan_baterai']) && $data['tegangan_baterai'] !== null
                        ? (float)$data['tegangan_baterai']
                        : null;

        $this->line("🔍 [SENSOR DEBUG] device={$deviceId}, kapasitas={$kapasitas}, tegangan=" . ($tegangan ?? 'null'));

        if (empty($deviceId)) {
            $this->error("❌ [SENSOR] device_id kosong");
            return;
        }

        $machine = Machine::where('is_simulation', false)->first() ?? Machine::first();
        if (!$machine) {
            $this->error("❌ [SENSOR] Tidak ada machine di DB");
            return;
        }

        $this->line("🔍 [SENSOR DEBUG] machine=#{$machine->id} ({$machine->nama_lokasi}, sim={$machine->is_simulation})");

        $oldCapacity = (int) $machine->kapasitas_terkini;

        // ▼▼▼ UPDATE DB — null-handling aman ▼▼▼
        $updateData = [
            'kapasitas_terkini' => $kapasitas,
            'status_online'     => true,
            'last_seen_at'      => now(),
        ];
        if ($tegangan !== null) {
            $updateData['tegangan_baterai'] = $tegangan;
        }

        try {
            $machine->update($updateData);
            $this->info("💾 [SENSOR] DB updated: kapasitas={$kapasitas}%, status_online=true" . ($tegangan !== null ? ", tegangan={$tegangan}V" : ""));
        } catch (\Throwable $e) {
            $this->error("❌ [SENSOR] DB update gagal: " . $e->getMessage());
            Log::error('[MqttRfidListener] Sensor DB update gagal', [
                'error'    => $e->getMessage(),
                'update'   => $updateData,
                'machine'  => $machine->id,
            ]);
            return;
        }

        // ▼▼▼ NOTIFIKASI — dipisah agar tidak crash ▼▼▼
        try {
            \App\Services\NotificationService::checkAndNotifyMachineCapacityTransition($machine, $oldCapacity);
        } catch (\Throwable $e) {
            $this->warn("⚠ [SENSOR] Notification gagal: " . $e->getMessage());
            Log::warning('[MqttRfidListener] Notification error', ['error' => $e->getMessage()]);
        }

        // ▼▼▼ BROADCAST — Reverb down tidak crash ▼▼▼
        try {
            broadcast(new NodeTelemetryUpdated(
                $machine->id,
                $kapasitas,
                true,
                $tegangan,
                $machine->nama_lokasi
            ));
            $this->line("📡 [SENSOR] Broadcast terkirim");
        } catch (\Throwable $e) {
            $this->warn("⚠ [SENSOR] Broadcast gagal (Reverb down?): " . $e->getMessage());
            Log::warning('[MqttRfidListener] Broadcast error', ['error' => $e->getMessage()]);
        }

        $this->info("📈 [TELEMETRY] {$machine->nama_lokasi} : {$kapasitas}%");
    }

    // ============================================================
    // UID HANDLER (pairing)
    // ============================================================
    private function handleUidMessage(string $topic, string $payload): void
    {
        $receivedAt = now()->format('Y-m-d H:i:s');

        $this->line('');
        $this->info("📶 PESAN MASUK [{$receivedAt}]");
        $this->line("Topik   : {$topic}");
        $this->line("Payload : {$payload}");

        $data = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('⚠ Payload bukan JSON valid — diabaikan');
            Log::warning('[MqttRfidListener] Payload tidak valid', [
                'topic'   => $topic,
                'payload' => $payload,
            ]);
            return;
        }

        $deviceId = trim($data['device_id'] ?? '');
        $uid      = strtoupper(trim($data['uid'] ?? ''));

        if (empty($deviceId) || empty($uid)) {
            $this->error('⚠ Field device_id atau uid kosong — diabaikan');
            Log::warning('[MqttRfidListener] Field wajib kosong', [
                'device_id' => $deviceId,
                'uid'       => $uid,
            ]);
            return;
        }

        $this->line("Device  : {$deviceId}");
        $this->line("UID     : {$uid}");

        $this->processRfidUid($deviceId, $uid);
    }

    private function processRfidUid(string $deviceId, string $uid): void
    {
        $isUsed = User::where('rfid_uid', $uid)->exists();
        if ($isUsed) {
            $this->warn("⚠ UID {$uid} sudah terdaftar ke akun lain — diabaikan");
            Log::warning('[MqttRfidListener] UID sudah terdaftar ke user lain', [
                'uid'       => $uid,
                'device_id' => $deviceId,
            ]);
            return;
        }

        $scanRequest = RfidScanRequest::where('device_id', $deviceId)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'asc')
            ->first();

        if (!$scanRequest) {
            $this->warn("⚠ Tidak ada scan request aktif untuk device {$deviceId} — UID diabaikan");
            Log::info('[MqttRfidListener] Tidak ada scan request aktif', [
                'uid'       => $uid,
                'device_id' => $deviceId,
            ]);
            return;
        }

        $this->line("Request : #{$scanRequest->id} (user_id: {$scanRequest->user_id})");

        $user = User::find($scanRequest->user_id);
        if (!$user) {
            $this->error("⚠ User ID {$scanRequest->user_id} tidak ditemukan di database");
            Log::error('[MqttRfidListener] User tidak ditemukan', [
                'user_id'    => $scanRequest->user_id,
                'request_id' => $scanRequest->id,
            ]);
            return;
        }

        try {
            $user->rfid_uid           = $uid;
            $user->rfid_registered_at = now();
            $user->save();

            \App\Models\CardRfid::updateOrCreate(
                ['user_id' => $user->id],
                ['uid_rfid' => $uid, 'status_aktif' => true]
            );

            $scanRequest->status   = 'completed';
            $scanRequest->rfid_uid = $uid;
            $scanRequest->save();

            $userLabel  = $user->email ?? $user->nama ?? "ID:{$user->id}";
            $successMsg = "Pairing BERHASIL! UID={$uid} -> User #{$user->id} ({$userLabel})";
            $this->info($successMsg);

            Log::info('[MqttRfidListener] Pairing RFID berhasil', [
                'uid'        => $uid,
                'user_id'    => $user->id,
                'device_id'  => $deviceId,
                'request_id' => $scanRequest->id,
            ]);
        } catch (\Throwable $e) {
            $this->error("❌ [PAIRING] Gagal simpan: " . $e->getMessage());
            Log::error('[MqttRfidListener] Pairing gagal', [
                'uid'   => $uid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ============================================================
    // DISPLAY HELPER
    // ============================================================
    private function printBanner(): void
    {
        $host     = config('mqtt.host');
        $port     = config('mqtt.port');
        $clientId = config('mqtt.listener_client_id');
        $useTls   = config('mqtt.tls');

        $this->line('');
        $this->line('╔══════════════════════════════════════════╗');
        $this->line('║      SOTO — MQTT RFID Listener v2        ║');
        $this->line('║      mqtt:listen-rfid                    ║');
        $this->line('╚══════════════════════════════════════════╝');
        $this->line('');
        $this->info("Host      : {$host}:{$port}");
        $this->info("Client ID : {$clientId}");
        $this->info('TLS       : ' . ($useTls ? '✅ enabled' : '❌ disabled'));
        $this->info('Subscribe : ' . self::TOPIC_UID);
        $this->line('');

        Log::info('[MqttRfidListener] Command dimulai', [
            'host'      => $host,
            'port'      => $port,
            'client_id' => $clientId,
            'topic'     => self::TOPIC_UID,
        ]);
    }
}
<?php

namespace App\Console\Commands;

use App\Models\RfidScanRequest;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Exceptions\MqttClientException;
use PhpMqtt\Client\MqttClient;

/**
 * MqttRfidListener
 *
 * Artisan command yang berjalan terus-menerus (long-running process) untuk
 * mendengarkan pesan UID kartu RFID dari ESP32 melalui HiveMQ Cloud.
 *
 * Alur kerja:
 *  1. ESP32 mendeteksi kartu RFID → publish UID ke soto/device/{id}/uid
 *  2. Command ini menerima pesan → validasi expiry & duplikat
 *  3. Simpan rfid_uid ke tabel users → update card_rfid status = completed
 *  4. Flutter polling /api/v1/rfid/status → detect completed → redirect /home
 *
 * Cara jalankan:
 *  Dev  : php artisan mqtt:listen-rfid
 *  Prod : gunakan Supervisor (lihat komentar di bawah)
 */
class MqttRfidListener extends Command
{
    /**
     * Nama command artisan.
     */
    protected $signature = 'mqtt:listen-rfid';

    /**
     * Deskripsi singkat command.
     */
    protected $description = 'Dengarkan UID RFID dari ESP32 via HiveMQ Cloud dan simpan ke database';

    /**
     * Topik yang di-subscribe. Wildcard "+" cocok dengan device_id apapun.
     * Contoh: soto/device/esp32-soto-01/uid
     */
    private const TOPIC_UID = 'soto/device/+/uid';

    /**
     * Objek MqttClient — dibuat ulang saat reconnect.
     */
    private ?MqttClient $mqtt = null;

    // =========================================================================
    // ENTRY POINT
    // =========================================================================

    public function handle(): int
    {
        $this->printBanner();

        // Loop reconnect — jika koneksi putus, coba sambung ulang setiap 5 detik
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

        // Baris ini tidak akan pernah tercapai (loop tak terbatas)
        return self::SUCCESS;
    }

    // =========================================================================
    // MQTT CONNECTION
    // =========================================================================

    /**
     * Buat instance MqttClient baru dengan konfigurasi dari mqtt.php.
     */
    private function createMqttClient(): MqttClient
    {
        $host     = config('mqtt.host');
        $port     = config('mqtt.port');
        $clientId = config('mqtt.listener_client_id');

        $this->info("[MQTT] Membuat client: {$host}:{$port} (ID: {$clientId})");

        return new MqttClient($host, $port, $clientId);
    }

    /**
     * Bangun ConnectionSettings dan hubungkan ke HiveMQ Cloud.
     *
     * @throws MqttClientException jika koneksi gagal
     */
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
        $this->mqtt->connect($settings, true); // clean session = true

        $this->info('[MQTT] ✅ Terhubung ke HiveMQ Cloud');
        Log::info('[MqttRfidListener] Terhubung ke HiveMQ Cloud', [
            'host'      => config('mqtt.host'),
            'port'      => config('mqtt.port'),
            'client_id' => config('mqtt.listener_client_id'),
        ]);
    }

    /**
     * Subscribe ke topik UID dan mulai loop blocking.
     *
     * @throws MqttClientException jika loop error
     */
    private function subscribeAndLoop(): void
    {
        $this->info('[MQTT] Subscribe ke topik: ' . self::TOPIC_UID);
        $this->info('[MQTT] Menunggu UID dari ESP32... (Ctrl+C untuk berhenti)');
        $this->line('');

        // Subscribe dengan QoS 1 agar pesan terjamin diterima
        $this->mqtt->subscribe(self::TOPIC_UID, function (string $topic, string $payload) {
            $this->handleUidMessage($topic, $payload);
        }, 1);

        // Loop blocking — akan throw exception jika koneksi drop
        $this->mqtt->loop(true);

        // Jika loop berhenti normal (bukan exception)
        $this->mqtt->disconnect();
    }

    // =========================================================================
    // MESSAGE HANDLER
    // =========================================================================

    /**
     * Proses pesan UID yang masuk dari ESP32.
     *
     * Payload yang diharapkan: {"device_id":"esp32-soto-01","uid":"43C210E2"}
     *
     * @param  string $topic   Topik MQTT, misal: soto/device/esp32-soto-01/uid
     * @param  string $payload JSON string dari ESP32
     */
    private function handleUidMessage(string $topic, string $payload): void
    {
        $receivedAt = now()->format('Y-m-d H:i:s');

        $this->line('');
        $this->line('========================================');
        $this->info("📶 PESAN MASUK [{$receivedAt}]");
        $this->line("Topik   : {$topic}");
        $this->line("Payload : {$payload}");
        $this->line('========================================');

        // --- Parse JSON ---
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

        // Validasi field wajib ada
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

        // --- Proses penyimpanan ke database ---
        $this->processRfidUid($deviceId, $uid);
    }

    /**
     * Validasi dan simpan UID RFID ke database.
     *
     * Langkah:
     *  1. Cek apakah UID sudah dipakai user lain
     *  2. Cari scan request pending yang masih valid (belum expired)
     *  3. Simpan rfid_uid + rfid_registered_at ke tabel users
     *  4. Update card_rfid: status = completed, rfid_uid = uid
     *
     * @param  string $deviceId  ID perangkat ESP32
     * @param  string $uid       UID kartu RFID uppercase
     */
    private function processRfidUid(string $deviceId, string $uid): void
    {
        // Langkah 1: Cek duplikasi UID
        $isUsed = User::where('rfid_uid', $uid)->exists();
        if ($isUsed) {
            $this->warn("⚠ UID {$uid} sudah terdaftar ke akun lain — diabaikan");
            Log::warning('[MqttRfidListener] UID sudah terdaftar ke user lain', [
                'uid'       => $uid,
                'device_id' => $deviceId,
            ]);
            return;
        }

        // Langkah 2: Cari scan request pending yang belum expired
        $scanRequest = RfidScanRequest::where('device_id', $deviceId)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'asc') // ambil yang paling lama (FIFO)
            ->first();

        if (! $scanRequest) {
            $this->warn("⚠ Tidak ada scan request aktif untuk device {$deviceId} — UID diabaikan");
            Log::info('[MqttRfidListener] Tidak ada scan request aktif', [
                'uid'       => $uid,
                'device_id' => $deviceId,
            ]);
            return;
        }

        $this->line("Request : #{$scanRequest->id} (user_id: {$scanRequest->user_id})");

        // Langkah 3: Simpan RFID ke user
        $user = User::find($scanRequest->user_id);
        if (! $user) {
            $this->error("⚠ User ID {$scanRequest->user_id} tidak ditemukan di database");
            Log::error('[MqttRfidListener] User tidak ditemukan', [
                'user_id'    => $scanRequest->user_id,
                'request_id' => $scanRequest->id,
            ]);
            return;
        }

        $user->rfid_uid           = $uid;
        $user->rfid_registered_at = now();
        $user->save();

        // Langkah 4: Update status scan request
        $scanRequest->status   = 'completed';
        $scanRequest->rfid_uid = $uid;
        $scanRequest->save();

        $userLabel  = $user->email ?? $user->nama ?? "ID:{$user->id}";
        $successMsg = "Pairing BERHASIL! UID={$uid} -> User #{$user->id} ({$userLabel})";
        $this->info($successMsg);
        $this->line('========================================');

        Log::info('[MqttRfidListener] Pairing RFID berhasil', [
            'uid'        => $uid,
            'user_id'    => $user->id,
            'device_id'  => $deviceId,
            'request_id' => $scanRequest->id,
        ]);
    }

    // =========================================================================
    // DISPLAY HELPER
    // =========================================================================

    /**
     * Tampilkan banner saat command pertama dijalankan.
     */
    private function printBanner(): void
    {
        $host     = config('mqtt.host');
        $port     = config('mqtt.port');
        $clientId = config('mqtt.listener_client_id');
        $useTls   = config('mqtt.tls');

        $this->line('');
        $this->line('╔══════════════════════════════════════════╗');
        $this->line('║      SOTO — MQTT RFID Listener           ║');
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

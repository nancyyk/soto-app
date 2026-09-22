<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Exceptions\MqttClientException;
use PhpMqtt\Client\MqttClient;

class MqttListen extends Command
{
    /**
     * Nama command artisan.
     */
    protected $signature = 'mqtt:listen';

    /**
     * Deskripsi command.
     */
    protected $description = 'Subscribe ke HiveMQ Cloud dan tampilkan data sensor ESP32';

    /**
     * Jalankan command.
     */
    public function handle(): int
    {
        $host = config('mqtt.host');
        $port = config('mqtt.port');
        $username = config('mqtt.username');
        $password = config('mqtt.password');
        $clientId = config('mqtt.client_id');
        $useTls = config('mqtt.tls');

        $this->info('');
        $this->info('========================================');
        $this->info('   SOTO MQTT LISTENER — Progress 1     ');
        $this->info('========================================');
        $this->info("Host      : {$host}:{$port}");
        $this->info("Client ID : {$clientId}");
        $this->info('TLS       : '.($useTls ? 'enabled' : 'disabled'));
        $this->info('');

        // --- Build connection settings ---
        $settings = (new ConnectionSettings)
            ->setUsername($username)
            ->setPassword($password)
            ->setKeepAliveInterval(config('mqtt.keep_alive', 60))
            ->setConnectTimeout(config('mqtt.connect_timeout', 30));

        if ($useTls) {
            $tlsOpts = config('mqtt.tls_settings', []);
            $settings = $settings
                ->setUseTls(true)
                ->setTlsVerifyPeer($tlsOpts['verify_peer'] ?? true)
                ->setTlsVerifyPeerName($tlsOpts['verify_peer_name'] ?? true);
        }

        // --- Connect ---
        try {
            $this->info('Menghubungkan ke HiveMQ Cloud...');
            $mqtt = new MqttClient($host, $port, $clientId);
            $mqtt->connect($settings, true); // clean session = true
            $this->info('Terhubung ke HiveMQ Cloud');
            $this->info('');
        } catch (MqttClientException $e) {
            $this->error('Gagal terhubung: '.$e->getMessage());

            return self::FAILURE;
        }

        // --- Subscribe ---
        $topic = 'soto/test';
        $this->info("Subscribing ke topic: {$topic}");
        $this->info('Menunggu data dari ESP32...');
        $this->info('(Tekan Ctrl+C untuk berhenti)');
        $this->info('');

        $mqtt->subscribe($topic, function (string $topic, string $payload) {
            $this->displayMessage($topic, $payload);
        }, 0); // QoS 0

        // --- Loop ---
        $mqtt->loop(true);

        // Jika loop berhenti
        $mqtt->disconnect();

        return self::SUCCESS;
    }

    /**
     * Parse payload JSON dan tampilkan ke terminal.
     */
    private function displayMessage(string $topic, string $payload): void
    {
        $receivedAt = now()->format('Y-m-d H:i:s');

        $this->line('');
        $this->line('========================================');
        $this->line('SOTO MQTT MESSAGE RECEIVED');
        $this->line('========================================');

        $data = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Payload bukan JSON yang valid:');
            $this->line($payload);
            $this->line('========================================');

            return;
        }

        $deviceId = $data['device_id'] ?? 'Unknown';

        $this->line("Topic       : {$topic}");
        $this->line("Device ID   : {$deviceId}");

        // --- Ultrasonic ---
        $this->line('');
        $this->line('ULTRASONIC');
        $ultrasonic = $data['ultrasonic'] ?? [];
        foreach (['sensor_1', 'sensor_2', 'sensor_3', 'sensor_4'] as $i => $key) {
            $label = 'Sensor '.($i + 1);
            $value = isset($ultrasonic[$key])
                ? number_format((float) $ultrasonic[$key], 1).' cm'
                : 'N/A';
            $this->line(str_pad($label, 12).': '.$value);
        }

        // --- Obstacle ---
        $this->line('');
        $this->line('OBSTACLE');
        $obstacle = $data['obstacle'] ?? [];
        foreach (['sensor_1', 'sensor_2'] as $i => $key) {
            $label = 'Sensor '.($i + 1);
            $detected = $obstacle[$key] ?? null;
            if ($detected === null) {
                $status = 'N/A';
            } elseif ($detected) {
                $status = 'DETECTED';
            } else {
                $status = 'NOT DETECTED';
            }
            $this->line(str_pad($label, 12).': '.$status);
        }

        // --- RFID ---
        $this->line('');
        $this->line('RFID');
        $uid = $data['rfid']['uid'] ?? 'N/A';
        $this->line(str_pad('UID', 12).': '.$uid);

        // --- DFPlayer ---
        $this->line('');
        $this->line('DFPLAYER');
        $dfplayer = $data['dfplayer'] ?? [];
        $dpStatus = $dfplayer['status'] ?? 'N/A';
        $track = $dfplayer['track'] ?? 'N/A';
        $this->line(str_pad('Status', 12).': '.$dpStatus);
        $this->line(str_pad('Track', 12).': '.$track);

        // --- Footer ---
        $this->line('');
        $this->line("Received    : {$receivedAt}");
        $this->line('========================================');
    }
}

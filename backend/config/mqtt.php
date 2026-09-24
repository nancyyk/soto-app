<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MQTT Connection Settings
    |--------------------------------------------------------------------------
    |
    | Konfigurasi koneksi ke HiveMQ Cloud menggunakan TLS (port 8883).
    | Semua nilai sensitif dibaca dari file .env.
    |
    */

    'host'     => env('MQTT_HOST', 'localhost'),
    'port'     => (int) env('MQTT_PORT', 8883),
    'username' => env('MQTT_USERNAME', ''),
    'password' => env('MQTT_PASSWORD', ''),
    'tls'      => (bool) env('MQTT_TLS', true),

    /*
    |--------------------------------------------------------------------------
    | Client IDs
    |--------------------------------------------------------------------------
    |
    | Setiap koneksi MQTT ke HiveMQ Cloud HARUS punya client_id unik.
    | Publisher (RfidController) dan Listener (MqttRfidListener) masing-masing
    | punya client_id sendiri agar tidak saling kick.
    |
    */

    // Dipakai oleh MqttListen (test command)
    'client_id' => env('MQTT_CLIENT_ID', 'laravel-soto-' . gethostname()),

    // Dipakai oleh RfidController::startScan() saat publish command ke ESP32
    'publisher_client_id' => env('MQTT_CLIENT_ID_PUBLISHER', 'laravel-rfid-publisher-' . gethostname()),

    // Dipakai oleh MqttRfidListener command yang subscribe soto/device/+/uid
    'listener_client_id' => env('MQTT_CLIENT_ID_LISTENER', 'laravel-rfid-listener-' . gethostname()),

    /*
    |--------------------------------------------------------------------------
    | TLS / SSL Options
    |--------------------------------------------------------------------------
    |
    | HiveMQ Cloud menggunakan sertifikat Let's Encrypt / DigiCert yang
    | sudah dipercaya secara publik, sehingga verify_peer bisa = true.
    | Jika ada masalah koneksi TLS, ubah verify_peer ke false untuk debug.
    |
    */

    'tls_settings' => [
        'verify_peer'      => true,
        'verify_peer_name' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Connection Behavior
    |--------------------------------------------------------------------------
    */

    'keep_alive'      => 60,
    'connect_timeout' => 30,
    'socket_timeout'  => 5,

];

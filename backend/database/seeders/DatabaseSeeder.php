<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Machine;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // --- Admin & Petugas Accounts -----------------------------------------
        User::updateOrCreate(['email' => 'admin@soto.test'], [
            'nama' => 'Super Admin SOTO',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
            'saldo_poin' => 0,
        ]);

        User::updateOrCreate(['email' => 'petugas@soto.test'], [
            'nama' => 'Petugas Kebersihan',
            'password' => Hash::make('password'),
            'role' => UserRole::Petugas,
            'saldo_poin' => 0,
        ]);

        // --- 5 RVM Machines (1 real + 4 simulation) --------------------------
        $machines = [
            [
                'nama_lokasi' => 'SOTO-1 Gedung A (Real Device)',
                'latitude' => -7.2836,
                'longitude' => 112.7953,
                'is_simulation' => false,
                'status_online' => false,
                'kapasitas_terkini' => 0,
                'threshold_capacity' => 80,
            ],
            [
                'nama_lokasi' => 'SOTO-2 Taman Kota (Simulasi)',
                'latitude' => -7.2800,
                'longitude' => 112.7970,
                'is_simulation' => true,
                'status_online' => true,
                'kapasitas_terkini' => 0,
                'threshold_capacity' => 80,
            ],
            [
                'nama_lokasi' => 'SOTO-3 Pasar Besar (Simulasi)',
                'latitude' => -7.2855,
                'longitude' => 112.8010,
                'is_simulation' => true,
                'status_online' => true,
                'kapasitas_terkini' => 0,
                'threshold_capacity' => 80,
            ],
            [
                'nama_lokasi' => 'SOTO-4 Kampus Utama (Simulasi)',
                'latitude' => -7.2770,
                'longitude' => 112.7920,
                'is_simulation' => true,
                'status_online' => true,
                'kapasitas_terkini' => 0,
                'threshold_capacity' => 80,
            ],
            [
                'nama_lokasi' => 'SOTO-5 Rumah Sakit (Simulasi)',
                'latitude' => -7.2890,
                'longitude' => 112.7880,
                'is_simulation' => true,
                'status_online' => true,
                'kapasitas_terkini' => 0,
                'threshold_capacity' => 80,
            ],
        ];

        foreach ($machines as $machine) {
            Machine::updateOrCreate(
                ['nama_lokasi' => $machine['nama_lokasi']],
                $machine
            );
        }

        // --- App Settings -----------------------------------------------------
        $settings = [
            ['key' => 'poin_per_botol',  'value' => '10',       'description' => 'Jumlah poin per botol yang disetorkan'],
            ['key' => 'depot_lat',       'value' => '-7.2750',  'description' => 'Latitude titik depot pengangkutan'],
            ['key' => 'depot_lng',       'value' => '112.7900', 'description' => 'Longitude titik depot pengangkutan'],
            ['key' => 'depot_nama',      'value' => 'Pos Pengangkutan Pusat', 'description' => 'Nama depot'],
            ['key' => 'tsp_threshold',   'value' => '80',       'description' => 'Threshold kapasitas (%) untuk trigger TSP'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}

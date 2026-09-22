<?php
namespace Database\Seeders;

use App\Models\Device;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        Device::create([
            'device_id' => 'esp32-soto-01',
            'name' => 'SOTO RVM Utama',
            'api_key' => 'secret_key_123',
            'is_active' => true,
        ]);
    }
}
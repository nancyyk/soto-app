<?php

namespace Tests\Feature;

use App\Models\Machine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_telemetry_requires_internal_secret()
    {
        $response = $this->postJson('/internal/telemetry', ['machine_id' => 1, 'kapasitas' => 50, 'status_online' => true]);
        $response->assertStatus(401);
    }

    public function test_telemetry_updates_machine_data()
    {
        config(['soto.internal_api_secret' => 'test-secret']);
        $machine = Machine::create([
            'nama_lokasi' => 'Test Location',
            'latitude' => 0.0,
            'longitude' => 0.0,
            'is_simulation' => true,
            'kapasitas_terkini' => 10,
            'tegangan_baterai' => 12.0,
        ]);
        $response = $this->withHeaders([
            'X-Internal-Secret' => 'test-secret',
        ])->postJson('/internal/telemetry', [
            'machine_id' => $machine->id,
            'kapasitas' => 85,
            'tegangan_baterai' => 11.5,
            'status_online' => true,
        ]);
        $response->assertStatus(200);
    }
}

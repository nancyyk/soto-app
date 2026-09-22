<?php

namespace App\Http\Controllers\Internal;

use App\Events\NodeTelemetryUpdated;
use App\Http\Controllers\Controller;
use App\Jobs\RecalculateRouteJob;
use App\Models\LogCapacity;
use App\Models\Machine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives telemetry updates from Node-RED (after Node-RED writes to MySQL directly).
 * Used only for broadcasting the event and triggering TSP if threshold is exceeded.
 *
 * Expected payload:
 * {
 *   "machine_id":        1,
 *   "kapasitas":         82,
 *   "tegangan_baterai":  12.6,
 *   "status_online":     true
 * }
 */
class InternalTelemetryController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'machine_id' => ['required', 'integer'],
            'kapasitas' => ['required', 'integer', 'min:0', 'max:100'],
            'tegangan_baterai' => ['nullable', 'numeric'],
            'status_online' => ['required', 'boolean'],
        ]);

        $machine = Machine::find($validated['machine_id']);
        if (! $machine) {
            return response()->json(['error' => 'Machine not found'], 404);
        }

        // Update machine current state
        $machine->update([
            'kapasitas_terkini' => $validated['kapasitas'],
            'tegangan_baterai' => $validated['tegangan_baterai'],
            'status_online' => $validated['status_online'],
        ]);

        // Log time-series
        LogCapacity::create([
            'machine_id' => $machine->id,
            'persen_kapasitas' => $validated['kapasitas'],
            'tegangan_baterai' => $validated['tegangan_baterai'],
            'created_at' => now(),
        ]);

        // Broadcast to dashboard
        event(new NodeTelemetryUpdated(
            machineId: $machine->id,
            kapasitas: $validated['kapasitas'],
            statusOnline: $validated['status_online'],
            teganganBaterai: $validated['tegangan_baterai'],
            namaLokasi: $machine->nama_lokasi,
        ));

        // Trigger TSP if threshold reached
        if ($machine->isAboveThreshold()) {
            RecalculateRouteJob::dispatch();
            Log::info('Threshold reached, TSP job dispatched', ['machine_id' => $machine->id]);
        }

        return response()->json(['status' => 'ok']);
    }
}

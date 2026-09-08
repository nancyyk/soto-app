<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Mesin RVM
 */
class MachineController extends Controller
{
    /**
     * Daftar semua mesin dan status terkini.
     *
     * @response 200 [{"id":1,"nama_lokasi":"SOTO-1","kapasitas_terkini":45,"status_online":true}]
     */
    public function index(): JsonResponse
    {
        return response()->json(Machine::all());
    }

    /**
     * Detail satu mesin + histori kapasitas ringkas (24 jam terakhir).
     *
     * @urlParam id integer required ID mesin. Example: 1
     */
    public function show(Machine $machine): JsonResponse
    {
        $machine->load(['logCapacity' => fn($q) => $q->where('created_at', '>=', now()->subHours(24))->latest()]);
        return response()->json($machine);
    }

    /**
     * Set kapasitas manual untuk node simulasi.
     *
     * @urlParam id integer required ID mesin simulasi. Example: 2
     * @bodyParam kapasitas integer required Nilai kapasitas 0-100. Example: 85
     */
    public function simulateCapacity(Request $request, Machine $machine): JsonResponse
    {
        if (!$machine->is_simulation) {
            return response()->json(['error' => 'Hanya node simulasi yang dapat diubah secara manual'], 422);
        }

        $validated = $request->validate([
            'kapasitas' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $machine->update(['kapasitas_terkini' => $validated['kapasitas']]);

        return response()->json($machine->fresh());
    }
}

<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessTransactionJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives transaction payloads from Node-RED and dispatches processing jobs.
 *
 * Called by Node-RED Flow 2 after validating MQTT transaction payload.
 *
 * Expected payload:
 * {
 *   "uid_rfid":     "A1:9F:22:0B",
 *   "machine_id":   1,
 *   "jumlah_botol": 3,
 *   "timestamp":    "2026-08-28T10:24:00Z"
 * }
 */
class InternalTransactionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'uid_rfid'     => ['required', 'string', 'max:50'],
            'machine_id'   => ['required', 'integer', 'min:1'],
            'jumlah_botol' => ['required', 'integer', 'min:1', 'max:255'],
            'timestamp'    => ['required', 'string'],
        ]);

        ProcessTransactionJob::dispatch(
            uidRfid:     $validated['uid_rfid'],
            machineId:   $validated['machine_id'],
            jumlahBotol: $validated['jumlah_botol'],
            timestamp:   $validated['timestamp'],
        );

        Log::info('Internal: transaction queued', ['uid' => $validated['uid_rfid']]);

        return response()->json(['status' => 'queued'], 202);
    }
}

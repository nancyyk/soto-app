<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Transaksi
 */
class TransactionController extends Controller
{
    /**
     * Riwayat transaksi (paginated).
     *
     * @queryParam machine_id integer Filter berdasarkan mesin. Example: 1
     * @queryParam date string Filter tanggal (Y-m-d). Example: 2026-08-28
     * @queryParam per_page integer Jumlah per halaman, default 20. Example: 20
     */
    public function index(Request $request): JsonResponse
    {
        $query = Transaction::with(['user:id,nama', 'machine:id,nama_lokasi'])
            ->latest('created_at');

        if ($request->filled('machine_id')) {
            $query->where('machine_id', $request->integer('machine_id'));
        }
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }
}

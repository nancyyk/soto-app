<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @group Laporan
 */
class ReportController extends Controller
{
    /**
     * Statistik ringkas (total botol, poin, per-node).
     */
    public function summary(): JsonResponse
    {
        $totalBotol = Transaction::sum('jumlah_botol');
        $totalPoin = Transaction::sum('poin_diperoleh');
        $today = Transaction::whereDate('created_at', today())->sum('jumlah_botol');

        $perNode = Machine::withSum(['transactions as total_botol' => fn ($q) => $q], 'jumlah_botol')
            ->withSum(['transactions as total_poin' => fn ($q) => $q], 'poin_diperoleh')
            ->get(['id', 'nama_lokasi', 'kapasitas_terkini', 'status_online']);

        return response()->json([
            'total_botol' => $totalBotol,
            'total_poin' => $totalPoin,
            'botol_hari_ini' => $today,
            'per_node' => $perNode,
        ]);
    }

    /**
     * Export CSV riwayat transaksi.
     *
     * @queryParam date_from string Dari tanggal (Y-m-d). Example: 2026-08-01
     * @queryParam date_to string Sampai tanggal (Y-m-d). Example: 2026-08-31
     */
    public function export(Request $request): Response
    {
        $query = Transaction::with(['user:id,nama,email', 'machine:id,nama_lokasi'])
            ->latest('created_at');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $transactions = $query->get();

        $csv = "ID,Nama Pengguna,Email,Lokasi Mesin,Jumlah Botol,Poin Diperoleh,Waktu\n";
        foreach ($transactions as $t) {
            $csv .= implode(',', [
                $t->id,
                '"'.($t->user?->nama ?? '-').'"',
                $t->user?->email ?? '-',
                '"'.($t->machine?->nama_lokasi ?? '-').'"',
                $t->jumlah_botol,
                $t->poin_diperoleh,
                $t->created_at?->toIso8601String(),
            ])."\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="soto-transactions.csv"',
        ]);
    }
}

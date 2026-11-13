<?php

namespace App\Http\Controllers\Admin;

use App\Exports\KinerjaLogistikExport;
use App\Exports\ManifesRuteExport;
use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\Route;
use App\Models\Setting;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LaporanRuteController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────
    // SHARED HELPERS
    // ─────────────────────────────────────────────────────────────────────

    private function latestRoute(): ?Route
    {
        return Route::with('stops.machine')->latest('id')->first();
    }

    private function depot(): array
    {
        return [
            'lat'  => (float) Setting::get('depot_lat',  -7.2750),
            'lng'  => (float) Setting::get('depot_lng',  112.7900),
            'nama' => Setting::get('depot_nama', 'Pos Pengangkutan'),
        ];
    }

    private function kinerjaData(): array
    {
        return Machine::withSum(
                ['transactions as total_botol' => fn($q) => $q], 'jumlah_botol'
            )
            ->withSum(
                ['transactions as total_poin' => fn($q) => $q], 'poin_diperoleh'
            )
            ->withCount('transactions as frekuensi_transaksi')
            ->orderBy('id')
            ->get()
            ->toArray();
    }

    // ─────────────────────────────────────────────────────────────────────
    // MANIFES RUTE – PDF
    // ─────────────────────────────────────────────────────────────────────

    public function manifestPdf(): Response
    {
        $route = $this->latestRoute();
        $depot = $this->depot();

        $pdf = Pdf::loadView('laporan.manifes-rute', [
            'route'      => $route,
            'depot'      => $depot,
            'printedAt'  => now()->isoFormat('dddd, D MMMM YYYY HH:mm') . ' WIB',
        ])->setPaper('a4', 'portrait');

        $filename = 'manifes-rute-soto-' . now()->format('Ymd-His') . '.pdf';

        return $pdf->download($filename);
    }

    // ─────────────────────────────────────────────────────────────────────
    // MANIFES RUTE – EXCEL
    // ─────────────────────────────────────────────────────────────────────

    public function manifestExcel(): BinaryFileResponse
    {
        $route = $this->latestRoute();
        $depot = $this->depot();

        if (! $route) {
            abort(404, 'Belum ada rute tersimpan.');
        }

        $filename = 'manifes-rute-soto-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new ManifesRuteExport($route, $depot), $filename);
    }

    // ─────────────────────────────────────────────────────────────────────
    // KINERJA LOGISTIK – PDF
    // ─────────────────────────────────────────────────────────────────────

    public function kinerjaPdf(): Response
    {
        $machines   = $this->kinerjaData();
        $totalBotol = Transaction::sum('jumlah_botol');
        $totalPoin  = Transaction::sum('poin_diperoleh');
        $totalTx    = Transaction::count();

        $pdf = Pdf::loadView('laporan.kinerja-logistik', [
            'machines'   => $machines,
            'totalBotol' => $totalBotol,
            'totalPoin'  => $totalPoin,
            'totalTx'    => $totalTx,
            'printedAt'  => now()->isoFormat('dddd, D MMMM YYYY HH:mm') . ' WIB',
        ])->setPaper('a4', 'portrait');

        $filename = 'kinerja-logistik-soto-' . now()->format('Ymd-His') . '.pdf';

        return $pdf->download($filename);
    }

    // ─────────────────────────────────────────────────────────────────────
    // KINERJA LOGISTIK – EXCEL
    // ─────────────────────────────────────────────────────────────────────

    public function kinerjaExcel(): BinaryFileResponse
    {
        $filename = 'kinerja-logistik-soto-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new KinerjaLogistikExport(), $filename);
    }
}

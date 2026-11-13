<?php

namespace App\Exports;

use App\Models\Machine;
use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Collection;

class KinerjaLogistikExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithColumnWidths
{
    public function title(): string
    {
        return 'Kinerja Logistik';
    }

    public function headings(): array
    {
        return [
            'No.',
            'Nama Lokasi (Node)',
            'Total Botol Terkumpul',
            'Total Poin Diberikan',
            'Frekuensi Transaksi',
            'Kapasitas Saat Ini (%)',
            'Status Online',
        ];
    }

    public function collection(): Collection
    {
        $machines = Machine::withSum(
                ['transactions as total_botol' => fn($q) => $q], 'jumlah_botol'
            )
            ->withSum(
                ['transactions as total_poin' => fn($q) => $q], 'poin_diperoleh'
            )
            ->withCount('transactions as frekuensi_transaksi')
            ->orderBy('id')
            ->get();

        $rows = collect();
        $no = 1;

        foreach ($machines as $machine) {
            $rows->push([
                $no++,
                $machine->nama_lokasi,
                (int) ($machine->total_botol ?? 0),
                (int) ($machine->total_poin ?? 0),
                (int) ($machine->frekuensi_transaksi ?? 0),
                $machine->kapasitas_terkini . '%',
                $machine->status_online ? 'Online' : 'Offline',
            ]);
        }

        // Baris total
        $rows->push([
            '',
            'TOTAL',
            (int) Transaction::sum('jumlah_botol'),
            (int) Transaction::sum('poin_diperoleh'),
            (int) Transaction::count(),
            '-',
            '-',
        ]);

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 32,
            'C' => 22,
            'D' => 22,
            'E' => 22,
            'F' => 20,
            'G' => 14,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E6B40']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}

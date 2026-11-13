<?php

namespace App\Exports;

use App\Models\Route;
use App\Models\Setting;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Collection;

class ManifesRuteExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithColumnWidths
{
    protected Route $route;
    protected array $depot;

    public function __construct(Route $route, array $depot)
    {
        $this->route = $route;
        $this->depot = $depot;
    }

    public function title(): string
    {
        return 'Manifes Rute';
    }

    public function headings(): array
    {
        return [
            'No. Urut',
            'Jenis',
            'Nama Lokasi',
            'Latitude',
            'Longitude',
            'Kapasitas (%)',
            'Keterangan',
        ];
    }

    public function collection(): Collection
    {
        $rows = collect();

        // Baris depot awal
        $rows->push([
            'D',
            'Depot',
            $this->depot['nama'],
            number_format($this->depot['lat'], 6),
            number_format($this->depot['lng'], 6),
            '-',
            'Titik Awal Keberangkatan',
        ]);

        // Stop-stop
        foreach ($this->route->stops->sortBy('urutan') as $stop) {
            if (!$stop->machine) continue;
            $rows->push([
                $stop->urutan,
                'Mesin',
                $stop->machine->nama_lokasi,
                number_format($stop->machine->latitude, 6),
                number_format($stop->machine->longitude, 6),
                $stop->machine->kapasitas_terkini . '%',
                'Kunjungan ke-' . $stop->urutan,
            ]);
        }

        // Baris depot akhir
        $rows->push([
            'D',
            'Depot',
            $this->depot['nama'],
            number_format($this->depot['lat'], 6),
            number_format($this->depot['lng'], 6),
            '-',
            'Kembali ke Depot (Sirkuit Tertutup)',
        ]);

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 10,
            'C' => 32,
            'D' => 14,
            'E' => 14,
            'F' => 14,
            'G' => 32,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Header row style
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E6B40']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}

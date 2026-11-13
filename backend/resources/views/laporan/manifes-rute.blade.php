<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Manifes Rute Pengangkutan – SOTO</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10pt; color: #1a1a1a; background: #fff; }

        /* ── KOP ── */
        .kop { border-bottom: 3px solid #1e6b40; padding-bottom: 12px; margin-bottom: 16px; }
        .kop-inner { display: table; width: 100%; }
        .kop-logo { display: table-cell; vertical-align: middle; width: 56px; }
        .kop-logo-box {
            width: 48px; height: 48px; background: #1e6b40;
            border-radius: 8px; color: #fff; font-size: 22pt;
            font-weight: bold; text-align: center; line-height: 48px;
        }
        .kop-text { display: table-cell; vertical-align: middle; padding-left: 10px; }
        .kop-title { font-size: 15pt; font-weight: bold; color: #1e6b40; }
        .kop-sub { font-size: 9pt; color: #555; margin-top: 2px; }
        .kop-right { display: table-cell; text-align: right; vertical-align: middle; font-size: 8.5pt; color: #555; }

        /* ── JUDUL LAPORAN ── */
        .laporan-title { text-align: center; margin-bottom: 14px; }
        .laporan-title h2 { font-size: 13pt; font-weight: bold; color: #1e6b40; }
        .laporan-title p { font-size: 9pt; color: #666; margin-top: 3px; }

        /* ── RINGKASAN ── */
        .summary { background: #f0faf5; border: 1px solid #a7d7bc; border-radius: 6px; padding: 10px 14px; margin-bottom: 16px; }
        .summary table { width: 100%; border-collapse: collapse; }
        .summary td { padding: 3px 8px; font-size: 9.5pt; }
        .summary td:first-child { font-weight: bold; color: #1e6b40; width: 38%; }
        .summary-title { font-weight: bold; font-size: 10pt; color: #1e6b40; margin-bottom: 6px; }

        /* ── TABEL RUTE ── */
        .section-title { font-size: 10.5pt; font-weight: bold; color: #333; margin-bottom: 8px; border-left: 4px solid #1e6b40; padding-left: 8px; }
        table.rute { width: 100%; border-collapse: collapse; font-size: 9pt; }
        table.rute thead tr { background: #1e6b40; color: #fff; }
        table.rute thead th { padding: 7px 8px; text-align: left; }
        table.rute tbody tr:nth-child(even) { background: #f5faf7; }
        table.rute tbody tr.depot-row { background: #d1f0e0; font-weight: bold; }
        table.rute tbody td { padding: 6px 8px; border-bottom: 1px solid #e5e5e5; }
        .badge-stop { background: #2563eb; color: #fff; border-radius: 10px; padding: 2px 7px; font-size: 8pt; font-weight: bold; }
        .badge-depot { background: #1e6b40; color: #fff; border-radius: 10px; padding: 2px 7px; font-size: 8pt; font-weight: bold; }
        .cap-high { color: #dc2626; font-weight: bold; }

        /* ── FOOTER ── */
        .footer { margin-top: 20px; border-top: 1px solid #ddd; padding-top: 10px; font-size: 8pt; color: #888; text-align: center; }
        .no-route { text-align: center; padding: 30px; color: #888; font-size: 11pt; }
    </style>
</head>
<body>

{{-- KOP --}}
<div class="kop">
    <div class="kop-inner">
        <div class="kop-logo">
            <div class="kop-logo-box">S</div>
        </div>
        <div class="kop-text">
            <div class="kop-title">SOTO – Smart Waste Collection</div>
            <div class="kop-sub">Sistem Optimasi Transportasi & Operasional Sampah</div>
        </div>
        <div class="kop-right">
            Dicetak: {{ $printedAt }}<br>
            Dokumen: Manifes Rute
        </div>
    </div>
</div>

{{-- JUDUL --}}
<div class="laporan-title">
    <h2>MANIFES RUTE PENGANGKUTAN SAMPAH</h2>
    <p>Rute Optimum Berbasis Algoritma TSP (Traveling Salesperson Problem)</p>
</div>

@if($route && $route->stops->isNotEmpty())

{{-- RINGKASAN --}}
<div class="summary">
    <div class="summary-title">📋 Ringkasan Rute</div>
    <table>
        <tr>
            <td>Tanggal Kalkulasi</td>
            <td>: {{ $route->created_at?->isoFormat('D MMMM YYYY, HH:mm') ?? '-' }} WIB</td>
            <td>Metode Jarak</td>
            <td>: {{ strtoupper($route->distance_source ?? 'haversine') }}</td>
        </tr>
        <tr>
            <td>Total Jarak</td>
            <td>: <strong>{{ number_format($route->total_distance_km, 2) }} km</strong></td>
            <td>Jumlah Titik Singgah</td>
            <td>: <strong>{{ $route->stops->count() }} node</strong></td>
        </tr>
        <tr>
            <td>Estimasi Durasi</td>
            <td>: <strong>{{ $route->total_duration_min ?? '-' }} menit</strong></td>
            <td>Pos Depot</td>
            <td>: {{ $depot['nama'] }}</td>
        </tr>
    </table>
</div>

{{-- TABEL URUTAN KUNJUNGAN --}}
<div class="section-title">Urutan Kunjungan Optimum</div>
<table class="rute">
    <thead>
        <tr>
            <th style="width:8%">No.</th>
            <th style="width:30%">Nama Lokasi</th>
            <th style="width:16%">Latitude</th>
            <th style="width:16%">Longitude</th>
            <th style="width:12%">Kapasitas</th>
            <th style="width:18%">Keterangan</th>
        </tr>
    </thead>
    <tbody>
        {{-- Depot Awal --}}
        <tr class="depot-row">
            <td><span class="badge-depot">D</span></td>
            <td>{{ $depot['nama'] }}</td>
            <td>{{ number_format($depot['lat'], 6) }}</td>
            <td>{{ number_format($depot['lng'], 6) }}</td>
            <td>–</td>
            <td>Titik Awal Keberangkatan</td>
        </tr>

        {{-- Stop-stop --}}
        @foreach($route->stops->sortBy('urutan') as $stop)
            @if($stop->machine)
            <tr>
                <td><span class="badge-stop">{{ $stop->urutan }}</span></td>
                <td>{{ $stop->machine->nama_lokasi }}</td>
                <td>{{ number_format($stop->machine->latitude, 6) }}</td>
                <td>{{ number_format($stop->machine->longitude, 6) }}</td>
                <td class="cap-high">{{ $stop->machine->kapasitas_terkini }}%</td>
                <td>Kunjungan ke-{{ $stop->urutan }}</td>
            </tr>
            @endif
        @endforeach

        {{-- Depot Akhir --}}
        <tr class="depot-row">
            <td><span class="badge-depot">D</span></td>
            <td>{{ $depot['nama'] }}</td>
            <td>{{ number_format($depot['lat'], 6) }}</td>
            <td>{{ number_format($depot['lng'], 6) }}</td>
            <td>–</td>
            <td>Kembali ke Depot (Sirkuit Tertutup)</td>
        </tr>
    </tbody>
</table>

@else
<div class="no-route">
    ⚠️ Belum ada rute tersimpan.<br>
    <small>Jalankan kalkulasi rute TSP terlebih dahulu di halaman admin.</small>
</div>
@endif

<div class="footer">
    Dokumen ini digenerate secara otomatis oleh Sistem SOTO &nbsp;|&nbsp; {{ $printedAt }} &nbsp;|&nbsp; Hanya untuk keperluan operasional internal
</div>

</body>
</html>

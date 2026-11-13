<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Kinerja Logistik – SOTO</title>
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

        /* ── KARTU STATISTIK ── */
        .stats-grid { width: 100%; margin-bottom: 16px; }
        .stats-table { width: 100%; border-collapse: separate; border-spacing: 8px 0; }
        .stat-card {
            background: #f0faf5; border: 1px solid #a7d7bc;
            border-radius: 6px; padding: 10px; text-align: center;
        }
        .stat-num { font-size: 14pt; font-weight: bold; color: #1e6b40; }
        .stat-label { font-size: 8pt; color: #555; margin-top: 2px; }

        /* ── TABEL DATA ── */
        .section-title { font-size: 10.5pt; font-weight: bold; color: #333; margin-bottom: 8px; border-left: 4px solid #1e6b40; padding-left: 8px; }
        table.data { width: 100%; border-collapse: collapse; font-size: 9pt; }
        table.data thead tr { background: #1e6b40; color: #fff; }
        table.data thead th { padding: 7px 8px; text-align: left; }
        table.data thead th.text-right { text-align: right; }
        table.data thead th.text-center { text-align: center; }
        table.data tbody tr:nth-child(even) { background: #f5faf7; }
        table.data tbody td { padding: 6px 8px; border-bottom: 1px solid #e5e5e5; }
        table.data tbody td.text-right { text-align: right; }
        table.data tbody td.text-center { text-align: center; }
        table.data tfoot tr { background: #e3f5ec; font-weight: bold; border-top: 2px solid #1e6b40; }
        table.data tfoot td { padding: 7px 8px; }
        table.data tfoot td.text-right { text-align: right; }

        .status-online { color: #16a34a; font-weight: bold; }
        .status-offline { color: #dc2626; font-weight: bold; }

        /* ── FOOTER ── */
        .footer { margin-top: 24px; border-top: 1px solid #ddd; padding-top: 10px; font-size: 8pt; color: #888; text-align: center; }
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
            Dokumen: Laporan Kinerja Logistik
        </div>
    </div>
</div>

{{-- JUDUL --}}
<div class="laporan-title">
    <h2>LAPORAN KINERJA LOGISTIK & PENGUMPULAN SAMPAH</h2>
    <p>Rekapitulasi Operasional Node Pengangkutan dan Akumulasi Transaksi</p>
</div>

{{-- KARTU STATISTIK RINGKASAN --}}
<div class="stats-grid">
    <table class="stats-table">
        <tr>
            <td style="width: 25%;">
                <div class="stat-card">
                    <div class="stat-num">{{ number_format($totalBotol) }}</div>
                    <div class="stat-label">Total Botol Terkumpul</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="stat-card">
                    <div class="stat-num">{{ number_format($totalPoin) }}</div>
                    <div class="stat-label">Total Poin Disalurkan</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="stat-card">
                    <div class="stat-num">{{ number_format($totalTx) }}</div>
                    <div class="stat-label">Total Frekuensi Transaksi</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="stat-card">
                    <div class="stat-num">{{ count($machines) }}</div>
                    <div class="stat-label">Total Node Terpasang</div>
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- TABEL KINERJA PER NODE --}}
<div class="section-title">Kinerja Operasional per Node Mesin</div>
<table class="data">
    <thead>
        <tr>
            <th style="width: 6%;" class="text-center">No.</th>
            <th style="width: 32%;">Nama Lokasi (Node)</th>
            <th style="width: 16%;" class="text-right">Total Botol</th>
            <th style="width: 16%;" class="text-right">Total Poin</th>
            <th style="width: 14%;" class="text-right">Frekuensi</th>
            <th style="width: 8%;" class="text-center">Kapasitas</th>
            <th style="width: 8%;" class="text-center">Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($machines as $index => $m)
        <tr>
            <td class="text-center">{{ $index + 1 }}</td>
            <td><strong>{{ $m['nama_lokasi'] }}</strong></td>
            <td class="text-right">{{ number_format($m['total_botol'] ?? 0) }}</td>
            <td class="text-right">{{ number_format($m['total_poin'] ?? 0) }}</td>
            <td class="text-right">{{ number_format($m['frekuensi_transaksi'] ?? 0) }}x</td>
            <td class="text-center">{{ $m['kapasitas_terkini'] }}%</td>
            <td class="text-center">
                @if($m['status_online'])
                    <span class="status-online">Online</span>
                @else
                    <span class="status-offline">Offline</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" class="text-center">TOTAL KESELURUHAN</td>
            <td class="text-right">{{ number_format($totalBotol) }}</td>
            <td class="text-right">{{ number_format($totalPoin) }}</td>
            <td class="text-right">{{ number_format($totalTx) }}x</td>
            <td class="text-center">–</td>
            <td class="text-center">–</td>
        </tr>
    </tfoot>
</table>

<div class="footer">
    Dokumen ini digenerate secara otomatis oleh Sistem SOTO &nbsp;|&nbsp; {{ $printedAt }} &nbsp;|&nbsp; Untuk keperluan pelaporan operasional instansi terkait
</div>

</body>
</html>

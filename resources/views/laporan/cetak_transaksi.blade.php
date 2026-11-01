<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Transaksi Penyewaan — Adventure Camp Rental</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            color: #1e293b;
            background-color: #f8fafc;
        }
        .print-container {
            max-width: 1100px;
            margin: 20px auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .kop-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .kop-brand {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .kop-sub {
            font-size: 11px;
            color: #64748b;
        }
        .report-title {
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .table-print th {
            background-color: #f1f5f9 !important;
            color: #0f172a;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 2px solid #cbd5e1;
        }
        .table-print td {
            font-size: 10.5px;
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        .recap-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 16px;
            margin-top: 15px;
        }
        .signature-box {
            margin-top: 40px;
        }
        @media print {
            body {
                background: #ffffff;
                color: #000000;
                font-size: 10px;
            }
            .print-container {
                max-width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
                border-radius: 0;
            }
            .no-print {
                display: none !important;
            }
            .table-print th {
                background-color: #e2e8f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

<!-- Floating Print Action Bar (Hidden on Print) -->
<div class="no-print bg-dark text-white py-2 px-3 sticky-top shadow-sm d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <strong>🖨️ Pratinjau Cetak Laporan Transaksi</strong>
        <span class="badge bg-secondary">Total: {{ $transaksiList->count() }} Data</span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button onclick="window.print()" class="btn btn-sm btn-success fw-bold px-3">
            🖨️ Cetak / Simpan PDF
        </button>
        <button onclick="window.close()" class="btn btn-sm btn-outline-light">
            ✕ Tutup Tab
        </button>
    </div>
</div>

<div class="print-container">
    <!-- Kop Surat Resmi -->
    <div class="kop-header d-flex justify-content-between align-items-center">
        <div>
            <div class="kop-brand">⛺ ADVENTURE CAMP RENTAL</div>
            <div class="kop-sub">Sistem Informasi Penyewaan Alat Camping & Outdoor Equipment Management</div>
            <div class="kop-sub">Jl. Kaliurang KM 12.5, Sleman, D.I. Yogyakarta &bull; Telp: (0274) 889977 &bull; Email: info@adventurecamp.test</div>
        </div>
        <div class="text-end">
            <span class="badge bg-dark text-white px-2 py-1 font-monospace" style="font-size: 9px;">OFFICIAL REPORT</span>
        </div>
    </div>

    <!-- Judul & Parameter Laporan -->
    <div class="text-center mb-3">
        <h1 class="report-title">Laporan Transaksi Penyewaan & Pembayaran</h1>
        <div class="text-muted" style="font-size: 11px;">
            Periode: 
            <strong>
                {{ !empty($filters['tanggal_mulai']) ? \Carbon\Carbon::parse($filters['tanggal_mulai'])->format('d/m/Y') : 'Awal' }}
                s.d.
                {{ !empty($filters['tanggal_selesai']) ? \Carbon\Carbon::parse($filters['tanggal_selesai'])->format('d/m/Y') : 'Sekarang' }}
            </strong>
            @if(!empty($filters['status_penyewaan']))
                &bull; Status Sewa: <strong>{{ $filters['status_penyewaan'] }}</strong>
            @endif
            @if(!empty($filters['status_pembayaran']))
                &bull; Status Bayar: <strong>{{ $filters['status_pembayaran'] }}</strong>
            @endif
        </div>
    </div>

    <!-- Info Metadata Pembuatan Dokumen -->
    <div class="row g-2 mb-3 small text-muted" style="font-size: 10px;">
        <div class="col-6">
            Dicetak Oleh: <strong>{{ $printedBy->nama_user ?? 'Admin' }} ({{ $printedBy->normalized_role ?? 'Staf' }})</strong>
        </div>
        <div class="col-6 text-end">
            Waktu Pembuatan: <strong>{{ $printedAt->format('d/m/Y H:i:s') }} WIB</strong>
        </div>
    </div>

    <!-- Tabel Data Laporan Transaksi -->
    <table class="table table-bordered table-print align-middle mb-2">
        <thead>
            <tr>
                <th style="width: 30px;" class="text-center">No</th>
                <th style="width: 110px;">No. Transaksi</th>
                <th style="width: 80px;">Tgl Sewa</th>
                <th style="width: 80px;">Tgl Kembali</th>
                <th>Nama Pelanggan</th>
                <th>Petugas</th>
                <th class="text-end" style="width: 100px;">Total Tagihan</th>
                <th class="text-end" style="width: 100px;">Jumlah Dibayar</th>
                <th class="text-end" style="width: 90px;">Sisa Tagihan</th>
                <th class="text-center" style="width: 80px;">Pembayaran</th>
                <th class="text-center" style="width: 85px;">Status Sewa</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transaksiList as $idx => $trx)
                @php
                    $tagihan = (float) $trx->total_biaya;
                    if ($trx->pengembalian?->denda) {
                        $tagihan += (float) $trx->pengembalian->denda->nominal_denda;
                    }
                    $isLunas = $trx->pembayaran && $trx->pembayaran->isLunas();
                    $dibayar = $isLunas ? (float) ($trx->pembayaran->jumlah_pembayaran > 0 ? $trx->pembayaran->jumlah_pembayaran : $tagihan) : 0.0;
                    $sisa = max(0.0, $tagihan - $dibayar);
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="font-monospace fw-bold">{{ $trx->no_transaksi }}</td>
                    <td>{{ $trx->tanggal_sewa ? \Carbon\Carbon::parse($trx->tanggal_sewa)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $trx->tanggal_kembali ? \Carbon\Carbon::parse($trx->tanggal_kembali)->format('d/m/Y') : '-' }}</td>
                    <td>
                        <strong>{{ $trx->pelanggan?->nama_pelanggan ?? 'Umum' }}</strong>
                        <small class="d-block text-muted" style="font-size: 9px;">{{ $trx->pelanggan?->nomor_telepon ?? '-' }}</small>
                    </td>
                    <td>{{ $trx->petugas_pemroses }}</td>
                    <td class="text-end font-monospace">Rp {{ number_format($tagihan, 0, ',', '.') }}</td>
                    <td class="text-end font-monospace {{ $dibayar > 0 ? 'text-success fw-bold' : '' }}">
                        Rp {{ number_format($dibayar, 0, ',', '.') }}
                    </td>
                    <td class="text-end font-monospace {{ $sisa > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                        Rp {{ number_format($sisa, 0, ',', '.') }}
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border" style="font-size: 9px;">
                            {{ $trx->status_pembayaran }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border" style="font-size: 9px;">
                            {{ $trx->status_penyewaan }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center py-3 text-muted">
                        Tidak ada catatan transaksi penyewaan untuk periode yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot class="table-light fw-bold" style="border-top: 2px solid #cbd5e1;">
            <tr>
                <td colspan="6" class="text-end">TOTAL REKAPITULASI LAPORAN:</td>
                <td class="text-end font-monospace">Rp {{ number_format($rekapTotalTagihan, 0, ',', '.') }}</td>
                <td class="text-end font-monospace text-success">Rp {{ number_format($rekapTotalKasDiterima, 0, ',', '.') }}</td>
                <td class="text-end font-monospace text-danger">Rp {{ number_format($rekapTotalSisaTagihan, 0, ',', '.') }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <!-- Kotak Ringkasan Rekapitulasi Keuangan -->
    <div class="recap-box">
        <div class="row text-center g-2" style="font-size: 11px;">
            <div class="col-3 border-end">
                <span class="text-muted d-block">Total Transaksi</span>
                <strong class="fs-6 font-monospace">{{ $rekapJumlahTransaksi }}</strong> Transaksi
            </div>
            <div class="col-3 border-end">
                <span class="text-muted d-block">Total Nilai Tagihan</span>
                <strong class="fs-6 font-monospace text-dark">Rp {{ number_format($rekapTotalTagihan, 0, ',', '.') }}</strong>
            </div>
            <div class="col-3 border-end">
                <span class="text-muted d-block">Penerimaan Kas Riil (Lunas)</span>
                <strong class="fs-6 font-monospace text-success">Rp {{ number_format($rekapTotalKasDiterima, 0, ',', '.') }}</strong>
            </div>
            <div class="col-3">
                <span class="text-muted d-block">Total Sisa Tagihan (Piutang)</span>
                <strong class="fs-6 font-monospace text-danger">Rp {{ number_format($rekapTotalSisaTagihan, 0, ',', '.') }}</strong>
            </div>
        </div>
    </div>

    <!-- Tanda Tangan Pengesahan Laporan -->
    <div class="row signature-box">
        <div class="col-6 text-center">
            <p class="mb-1 text-muted">Petugas Pembuat Laporan,</p>
            <div style="height: 60px;"></div>
            <p class="mb-0 fw-bold text-decoration-underline">{{ $printedBy->nama_user ?? 'Admin Operasional' }}</p>
            <small class="text-muted">{{ $printedBy->normalized_role ?? 'Staff Administrasi' }}</small>
        </div>
        <div class="col-6 text-center">
            <p class="mb-1 text-muted">Sleman, {{ $printedAt->translatedFormat('d F Y') }}<br>Mengetahui / Penanggung Jawab,</p>
            <div style="height: 60px;"></div>
            <p class="mb-0 fw-bold text-decoration-underline">Pimpinan / Pemilik Usaha</p>
            <small class="text-muted">Adventure Camp Rental</small>
        </div>
    </div>
</div>

</body>
</html>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Inventaris & Stok — Adventure Camp Rental</title>
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
        <strong>🖨️ Pratinjau Cetak Laporan Inventaris</strong>
        <span class="badge bg-secondary">
            {{ $tab === 'mutasi' ? ($riwayatMutasi ? $riwayatMutasi->count() : 0) . ' Mutasi' : $inventarisList->count() . ' Alat' }}
        </span>
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
        <h1 class="report-title">
            {{ $tab === 'mutasi' ? 'Laporan Riwayat Mutasi & Pergerakan Stok Alat' : 'Laporan Ketersediaan Stok & Kondisi Inventaris' }}
        </h1>
        <div class="text-muted" style="font-size: 11px;">
            @if($tab === 'mutasi')
                Periode: 
                <strong>
                    {{ !empty($filters['mutasi_tgl_mulai']) ? \Carbon\Carbon::parse($filters['mutasi_tgl_mulai'])->format('d/m/Y') : 'Awal' }}
                    s.d.
                    {{ !empty($filters['mutasi_tgl_selesai']) ? \Carbon\Carbon::parse($filters['mutasi_tgl_selesai'])->format('d/m/Y') : 'Sekarang' }}
                </strong>
            @else
                Filter Kategori: <strong>{{ !empty($filters['kategori']) ? $filters['kategori'] : 'Semua Kategori' }}</strong>
                &bull; Status: <strong>{{ !empty($filters['status_stok']) ? strtoupper($filters['status_stok']) : 'SEMUA' }}</strong>
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

    @if($tab === 'mutasi' && $riwayatMutasi)
        <!-- Tabel Data Riwayat Mutasi Stok -->
        <table class="table table-bordered table-print align-middle mb-2">
            <thead>
                <tr>
                    <th style="width: 30px;" class="text-center">No</th>
                    <th style="width: 100px;">Waktu</th>
                    <th style="width: 80px;">Kode Alat</th>
                    <th>Nama Alat Camping</th>
                    <th style="width: 120px;">Jenis Mutasi</th>
                    <th style="width: 80px;" class="text-center">Perubahan</th>
                    <th style="width: 60px;" class="text-center">Sebelum</th>
                    <th style="width: 60px;" class="text-center">Sesudah</th>
                    <th>Keterangan / Alasan</th>
                    <th style="width: 90px;">Petugas</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riwayatMutasi as $idx => $m)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="font-monospace small">{{ $m->created_at ? \Carbon\Carbon::parse($m->created_at)->format('d/m/Y H:i') : '-' }}</td>
                        <td class="font-monospace fw-bold">{{ $m->kode_alat }}</td>
                        <td>{{ $m->alat?->nama_alat ?? '-' }}</td>
                        <td>{{ $m->jenis_perubahan }}</td>
                        <td class="text-center font-monospace fw-bold {{ $m->jumlah_perubahan > 0 ? 'text-success' : 'text-danger' }}">
                            {{ $m->jumlah_perubahan > 0 ? '+' . $m->jumlah_perubahan : $m->jumlah_perubahan }}
                        </td>
                        <td class="text-center font-monospace text-muted">{{ $m->stok_sebelum }}</td>
                        <td class="text-center font-monospace fw-bold">{{ $m->stok_sesudah }}</td>
                        <td class="small">{{ $m->alasan ?? '-' }}</td>
                        <td class="small">{{ $m->user?->nama_user ?? 'Sistem' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-3 text-muted">
                            Tidak ada catatan mutasi stok untuk periode yang dipilih.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @else
        <!-- Tabel Data Status Inventaris & Stok -->
        <table class="table table-bordered table-print align-middle mb-2">
            <thead>
                <tr>
                    <th style="width: 30px;" class="text-center">No</th>
                    <th style="width: 75px;">Kode</th>
                    <th>Nama Alat Camping</th>
                    <th style="width: 85px;">Kategori</th>
                    <th style="width: 70px;">Kondisi</th>
                    <th class="text-end" style="width: 85px;">Tarif/Hari</th>
                    <th class="text-center" style="width: 60px;">Total Fisik</th>
                    <th class="text-center" style="width: 65px;">Siap Sewa</th>
                    <th class="text-center" style="width: 65px;">Sedang Disewa</th>
                    <th class="text-center" style="width: 70px;">Dialokasi</th>
                    <th class="text-center" style="width: 65px;">Bermasalah</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $sumFisik = 0;
                    $sumSiap = 0;
                    $sumSewa = 0;
                    $sumAlok = 0;
                    $sumBermasalah = 0;
                @endphp
                @forelse($inventarisList as $idx => $item)
                    @php
                        $sumFisik += $item->stok_fisik_total;
                        $sumSiap += $item->stok_siap_sewa;
                        $sumSewa += $item->stok_sedang_disewa;
                        $sumAlok += $item->stok_dialokasikan;
                        $sumBermasalah += $item->stok_bermasalah;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="font-monospace fw-bold">{{ $item->kode_alat }}</td>
                        <td>
                            <strong>{{ $item->nama_alat }}</strong>
                        </td>
                        <td>{{ $item->kategori }}</td>
                        <td>{{ $item->kondisi }}</td>
                        <td class="text-end font-monospace">Rp {{ number_format((float) $item->tarif_sewa, 0, ',', '.') }}</td>
                        <td class="text-center font-monospace fw-bold">{{ $item->stok_fisik_total }}</td>
                        <td class="text-center font-monospace {{ $item->stok_siap_sewa > 0 ? 'text-success fw-bold' : 'text-danger' }}">
                            {{ $item->stok_siap_sewa }}
                        </td>
                        <td class="text-center font-monospace {{ $item->stok_sedang_disewa > 0 ? 'text-warning fw-bold' : '' }}">
                            {{ $item->stok_sedang_disewa }}
                        </td>
                        <td class="text-center font-monospace {{ $item->stok_dialokasikan > 0 ? 'text-primary fw-bold' : '' }}">
                            {{ $item->stok_dialokasikan }}
                        </td>
                        <td class="text-center font-monospace {{ $item->stok_bermasalah > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                            {{ $item->stok_bermasalah }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center py-3 text-muted">
                            Tidak ada data inventaris yang cocok dengan kriteria filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="table-light fw-bold" style="border-top: 2px solid #cbd5e1;">
                <tr>
                    <td colspan="6" class="text-end">TOTAL REKAPITULASI INVENTARIS:</td>
                    <td class="text-center font-monospace">{{ $sumFisik }}</td>
                    <td class="text-center font-monospace text-success">{{ $sumSiap }}</td>
                    <td class="text-center font-monospace text-warning">{{ $sumSewa }}</td>
                    <td class="text-center font-monospace text-primary">{{ $sumAlok }}</td>
                    <td class="text-center font-monospace text-danger">{{ $sumBermasalah }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- Kotak Ringkasan Rekapitulasi Inventaris -->
        <div class="recap-box">
            <div class="row text-center g-2" style="font-size: 11px;">
                <div class="col-2 border-end">
                    <span class="text-muted d-block">Total Jenis (SKU)</span>
                    <strong class="fs-6 font-monospace">{{ count($inventarisList) }}</strong> Item
                </div>
                <div class="col-2 border-end">
                    <span class="text-muted d-block">Total Stok Fisik</span>
                    <strong class="fs-6 font-monospace">{{ $sumFisik }}</strong> Unit
                </div>
                <div class="col-2 border-end">
                    <span class="text-muted d-block">Siap Disewa</span>
                    <strong class="fs-6 font-monospace text-success">{{ $sumSiap }}</strong> Unit
                </div>
                <div class="col-2 border-end">
                    <span class="text-muted d-block">Sedang Disewa</span>
                    <strong class="fs-6 font-monospace text-warning">{{ $sumSewa }}</strong> Unit
                </div>
                <div class="col-2 border-end">
                    <span class="text-muted d-block">Dialokasi di Toko</span>
                    <strong class="fs-6 font-monospace text-primary">{{ $sumAlok }}</strong> Unit
                </div>
                <div class="col-2">
                    <span class="text-muted d-block">Total Belanja PO</span>
                    <strong class="fs-6 font-monospace text-dark">Rp {{ number_format($totalBiayaPembelian, 0, ',', '.') }}</strong>
                </div>
            </div>
        </div>
    @endif

    <!-- Tanda Tangan Pengesahan Laporan -->
    <div class="row signature-box">
        <div class="col-6 text-center">
            <p class="mb-1 text-muted">Petugas Pengelola Inventaris,</p>
            <div style="height: 60px;"></div>
            <p class="mb-0 fw-bold text-decoration-underline">{{ $printedBy->nama_user ?? 'Admin Gudang / Operasional' }}</p>
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


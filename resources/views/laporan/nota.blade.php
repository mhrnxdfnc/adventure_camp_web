<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Transaksi #{{ $penyewaan->no_transaksi }} — Adventure Camp Rental</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11.5px;
            color: #1e293b;
            background-color: #f1f5f9;
            margin: 0;
            padding: 20px 0;
        }
        .invoice-card {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            padding: 35px 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }
        .kop-nota {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .kop-brand {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .kop-sub {
            font-size: 11px;
            color: #64748b;
        }
        .invoice-title-badge {
            display: inline-block;
            background: #0f172a;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
            padding: 4px 12px;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .info-label {
            font-size: 10.5px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 2px;
        }
        .info-value {
            font-size: 12px;
            font-weight: 600;
            color: #0f172a;
        }
        .table-items th {
            background-color: #f8fafc;
            color: #475569;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 2px solid #cbd5e1;
            padding: 8px 10px;
        }
        .table-items td {
            font-size: 11.5px;
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .financial-summary {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 14px 18px;
        }
        .terms-box {
            font-size: 10px;
            color: #64748b;
            border-left: 3px solid #cbd5e1;
            padding-left: 10px;
            margin-top: 20px;
            line-height: 1.5;
        }
        .signature-section {
            margin-top: 35px;
        }
        .action-bar {
            max-width: 800px;
            margin: 0 auto 15px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        @media print {
            body {
                background: #ffffff;
                color: #000000;
                padding: 0;
            }
            .invoice-card {
                max-width: 100%;
                margin: 0;
                padding: 10px 15px;
                box-shadow: none;
                border: none;
            }
            .action-bar {
                display: none !important;
            }
            .table-items th {
                background-color: #eeeeee !important;
                color: #000000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .financial-summary {
                background-color: #fafafa !important;
                border: 1px solid #cccccc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .invoice-title-badge {
                background: #000000 !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            @page {
                size: A4 portrait;
                margin: 10mm 12mm;
            }
        }
    </style>
</head>
<body>

    <!-- Action Bar (Hanya tampil di layar) -->
    <div class="action-bar">
        <div>
            <button onclick="window.history.back()" class="btn btn-sm btn-outline-secondary">
                &larr; Kembali
            </button>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-sm btn-primary fw-semibold">
                &#128438; Cetak Nota (Print / PDF)
            </button>
        </div>
    </div>

    <!-- Container Nota Resmi -->
    <div class="invoice-card">
        <!-- KOP NOTA -->
        <div class="kop-nota">
            <div class="row align-items-center">
                <div class="col-7">
                    <div class="kop-brand">ADVENTURE CAMP RENTAL</div>
                    <div class="kop-sub">
                        Penyewaan Alat Camping, Hiking, & Perlengkapan Outdoor Terlengkap<br>
                        Jl. Kaliurang KM 14.5, Sleman, D.I. Yogyakarta | Telp/WA: 0812-3456-7890<br>
                        Email: info@adventurecamp.local | Jam Operasional: 08.00 - 21.00 WIB
                    </div>
                </div>
                <div class="col-5 text-end">
                    <span class="invoice-title-badge">NOTA TRANSAKSI SEWA</span>
                    <div class="mt-2">
                        <span class="info-label d-block">Nomor Transaksi:</span>
                        <span class="info-value text-primary fs-6 font-monospace">{{ $penyewaan->no_transaksi }}</span>
                    </div>
                    <div class="mt-1">
                        <span class="text-muted" style="font-size: 10px;">Tanggal Cetak: {{ $printedAt->translatedFormat('d F Y, H:i') }} WIB</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- INFORMASI PELANGGAN & DETAIL SEWA -->
        <div class="row g-3 mb-3">
            <div class="col-6">
                <div class="p-2 border rounded bg-light bg-opacity-50 h-100">
                    <div class="fw-bold text-dark border-bottom pb-1 mb-2 text-uppercase" style="font-size: 10.5px;">
                        Data Penyewa / Pelanggan
                    </div>
                    <table class="table table-borderless table-sm m-0" style="font-size: 11px;">
                        <tr>
                            <td class="text-muted p-0 py-0.5" style="width: 100px;">Nama Lengkap</td>
                            <td class="fw-bold text-dark p-0 py-0.5">: {{ $penyewaan->pelanggan?->nama ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted p-0 py-0.5">No. Telepon/WA</td>
                            <td class="p-0 py-0.5">: {{ $penyewaan->pelanggan?->no_telepon ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted p-0 py-0.5">Alamat</td>
                            <td class="p-0 py-0.5">: {{ $penyewaan->pelanggan?->alamat ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted p-0 py-0.5">No. Identitas</td>
                            <td class="p-0 py-0.5">: {{ $penyewaan->pelanggan?->no_identitas ?? '-' }} ({{ $penyewaan->pelanggan?->jenis_identitas ?? 'KTP/SIM' }})</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="col-6">
                <div class="p-2 border rounded bg-light bg-opacity-50 h-100">
                    <div class="fw-bold text-dark border-bottom pb-1 mb-2 text-uppercase" style="font-size: 10.5px;">
                        Jadwal & Status Penyewaan
                    </div>
                    <table class="table table-borderless table-sm m-0" style="font-size: 11px;">
                        <tr>
                            <td class="text-muted p-0 py-0.5" style="width: 110px;">Tgl Pengambilan</td>
                            <td class="fw-semibold text-dark p-0 py-0.5">: {{ \Carbon\Carbon::parse($penyewaan->tanggal_sewa)->translatedFormat('d F Y') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted p-0 py-0.5">Tgl Rencana Kembali</td>
                            <td class="fw-semibold text-dark p-0 py-0.5">: {{ \Carbon\Carbon::parse($penyewaan->tanggal_kembali)->translatedFormat('d F Y') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted p-0 py-0.5">Durasi Sewa</td>
                            <td class="fw-bold text-dark p-0 py-0.5">: {{ $penyewaan->durasi_sewa }} Hari</td>
                        </tr>
                        <tr>
                            <td class="text-muted p-0 py-0.5">Sumber Pesanan</td>
                            <td class="p-0 py-0.5">:
                                <span class="badge {{ $penyewaan->sumber_pesanan === 'Onsite' ? 'bg-secondary' : 'bg-info text-dark' }}" style="font-size: 9.5px;">
                                    {{ $penyewaan->sumber_pesanan ?? 'Website' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted p-0 py-0.5">Status Sewa</td>
                            <td class="p-0 py-0.5">:
                                <span class="badge bg-dark" style="font-size: 9.5px;">{{ $penyewaan->status_penyewaan }}</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- TABEL RINCIAN PERLENGKAPAN ALAT -->
        <table class="table table-items w-100 mb-3">
            <thead>
                <tr>
                    <th style="width: 35px;" class="text-center">No</th>
                    <th style="width: 90px;">Kode Alat</th>
                    <th>Nama Perlengkapan / Alat Camping</th>
                    <th>Kategori</th>
                    <th class="text-end" style="width: 100px;">Tarif/Hari</th>
                    <th class="text-center" style="width: 60px;">Qty</th>
                    <th class="text-center" style="width: 60px;">Durasi</th>
                    <th class="text-end" style="width: 110px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($penyewaan->detailPenyewaan as $idx => $detail)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="font-monospace text-muted">{{ $detail->alat?->kode_alat ?? '-' }}</td>
                        <td class="fw-bold text-dark">{{ $detail->alat?->nama_alat ?? 'Alat Camping' }}</td>
                        <td>{{ $detail->alat?->kategori ?? '-' }}</td>
                        <td class="text-end">Rp {{ number_format((float) ($detail->alat?->tarif_sewa ?? 0), 0, ',', '.') }}</td>
                        <td class="text-center fw-semibold">{{ $detail->jumlah }} Unit</td>
                        <td class="text-center">{{ $penyewaan->durasi_sewa }} Hari</td>
                        <td class="text-end fw-bold text-dark">Rp {{ number_format((float) $detail->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-3 text-muted">Tidak ada rincian item alat pada transaksi ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- RINCIAN FINANSIAL & STATUS PEMBAYARAN -->
        <div class="row align-items-start mb-2">
            <div class="col-6">
                <!-- Status Pembayaran Detail Box -->
                <div class="p-2 border rounded bg-white">
                    <div class="fw-bold text-dark border-bottom pb-1 mb-2 text-uppercase" style="font-size: 10.5px;">
                        Status & Metode Pembayaran
                    </div>
                    @php
                        $pembayaran = $penyewaan->pembayaran;
                        $statusBayar = $pembayaran?->status_pembayaran ?? 'Belum Dibayar';
                        $isLunas = in_array(strtolower($statusBayar), ['lunas', 'diverifikasi', 'selesai', 'sukses']);
                    @endphp
                    <table class="table table-borderless table-sm m-0" style="font-size: 11px;">
                        <tr>
                            <td class="text-muted p-0 py-0.5" style="width: 110px;">Status Bayar</td>
                            <td class="p-0 py-0.5">:
                                @if($isLunas)
                                    <span class="badge bg-success" style="font-size: 10px;">LUNAS</span>
                                @elseif(in_array(strtolower($statusBayar), ['menunggu', 'pending']))
                                    <span class="badge bg-warning text-dark" style="font-size: 10px;">MENUNGGU VERIFIKASI</span>
                                @else
                                    <span class="badge bg-danger" style="font-size: 10px;">{{ strtoupper($statusBayar) }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted p-0 py-0.5">Metode Bayar</td>
                            <td class="p-0 py-0.5">: {{ $pembayaran?->metode_pembayaran ?? ($penyewaan->sumber_pesanan === 'Onsite' ? 'Tunai / Kasir' : 'Transfer Manual') }}</td>
                        </tr>
                        @if($pembayaran?->tanggal_pembayaran)
                        <tr>
                            <td class="text-muted p-0 py-0.5">Tgl Pembayaran</td>
                            <td class="p-0 py-0.5">: {{ \Carbon\Carbon::parse($pembayaran->tanggal_pembayaran)->translatedFormat('d F Y, H:i') }}</td>
                        </tr>
                        @endif
                        @if($pembayaran?->admin)
                        <tr>
                            <td class="text-muted p-0 py-0.5">Diverifikasi Oleh</td>
                            <td class="p-0 py-0.5">: {{ $pembayaran->admin->nama_lengkap ?? $pembayaran->admin->username }}</td>
                        </tr>
                        @endif
                        @if($penyewaan->pengembalian)
                        <tr>
                            <td class="text-muted p-0 py-0.5">Kondisi Kembali</td>
                            <td class="p-0 py-0.5">: {{ $penyewaan->pengembalian->kondisi_alat_kembali ?? 'Sesuai' }}</td>
                        </tr>
                        @endif
                    </table>
                </div>

                <div class="terms-box">
                    <strong>Ketentuan Penyewaan:</strong><br>
                    1. Wajib menitipkan kartu identitas asli (KTP/SIM/KTM) yang masih berlaku selama masa sewa.<br>
                    2. Cek perlengkapan bersama petugas saat pengambilan dan pengembalian barang.<br>
                    3. Keterlambatan pengembalian dikenakan denda sesuai tarif harian per alat.<br>
                    4. Kerusakan atau kehilangan alat menjadi tanggung jawab penuh penyewa.
                </div>
            </div>

            <div class="col-6">
                <!-- Ringkasan Nominal Finansial -->
                @php
                    $dendaNominal = (float) ($penyewaan->pengembalian?->denda?->nominal_denda ?? 0);
                    $subtotalSewa = (float) $penyewaan->total_biaya;
                    $grandTotal = $subtotalSewa + $dendaNominal;
                    $terbayar = $isLunas ? (float) ($pembayaran?->jumlah_pembayaran > 0 ? $pembayaran->jumlah_pembayaran : $grandTotal) : 0;
                    $sisaTagihan = max(0, $grandTotal - $terbayar);
                @endphp
                <div class="financial-summary">
                    <table class="table table-borderless table-sm m-0" style="font-size: 11.5px;">
                        <tr>
                            <td class="text-muted p-0 py-1">Subtotal Biaya Sewa</td>
                            <td class="text-end fw-semibold p-0 py-1">Rp {{ number_format($subtotalSewa, 0, ',', '.') }}</td>
                        </tr>
                        @if($dendaNominal > 0)
                        <tr>
                            <td class="text-danger p-0 py-1">
                                Denda Pengembalian
                                <small class="d-block text-muted" style="font-size: 9px;">
                                    ({{ $penyewaan->pengembalian?->denda?->keterangan ?? 'Keterlambatan/Kerusakan' }})
                                </small>
                            </td>
                            <td class="text-end text-danger fw-bold p-0 py-1">+ Rp {{ number_format($dendaNominal, 0, ',', '.') }}</td>
                        </tr>
                        @endif
                        <tr class="border-top">
                            <td class="fw-bold text-dark pt-2" style="font-size: 13px;">TOTAL TAGIHAN</td>
                            <td class="text-end fw-bold text-dark pt-2 font-monospace" style="font-size: 14px;">
                                Rp {{ number_format($grandTotal, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted p-0 py-1">Pembayaran Diterima</td>
                            <td class="text-end fw-semibold text-success p-0 py-1">Rp {{ number_format($terbayar, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="border-top">
                            <td class="fw-bold {{ $sisaTagihan > 0 ? 'text-danger' : 'text-muted' }} pt-1">
                                {{ $sisaTagihan > 0 ? 'SISA TAGIHAN / PIUTANG' : 'SISA TAGIHAN' }}
                            </td>
                            <td class="text-end fw-bold {{ $sisaTagihan > 0 ? 'text-danger' : 'text-success' }} pt-1 font-monospace" style="font-size: 13px;">
                                Rp {{ number_format($sisaTagihan, 0, ',', '.') }}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- TANDA TANGAN BUKTI TRANSAKSI -->
        <div class="signature-section">
            <div class="row text-center">
                <div class="col-6">
                    <p class="mb-1 text-muted" style="font-size: 10.5px;">Penyewa / Pelanggan,</p>
                    <div style="height: 55px;"></div>
                    <p class="fw-bold text-dark mb-0 text-decoration-underline">{{ $penyewaan->pelanggan?->nama ?? 'Penyewa' }}</p>
                    <small class="text-muted">Penyewa</small>
                </div>
                <div class="col-6">
                    <p class="mb-1 text-muted" style="font-size: 10.5px;">Petugas Operasional / Kasir,</p>
                    <div style="height: 55px;"></div>
                    <p class="fw-bold text-dark mb-0 text-decoration-underline">
                        {{ $penyewaan->petugas_pemroses?->nama_lengkap ?? $printedBy->nama_lengkap ?? 'Petugas Adventure Camp' }}
                    </p>
                    <small class="text-muted">Adventure Camp Rental</small>
                </div>
            </div>
        </div>
    </div>

</body>
</html>


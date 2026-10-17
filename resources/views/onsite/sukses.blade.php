@extends('layouts.onsite')

@section('title', 'Pesanan On-Site Berhasil — ' . $penyewaan->no_transaksi)

@section('content')
<div class="row justify-content-center py-md-3">
    <div class="col-12 col-lg-8 col-xl-7">
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-white">
            <!-- Header Kartu Sukses -->
            <div class="p-4 p-md-5 text-center text-white" style="background: linear-gradient(135deg, #1b4332 0%, #2d6a4f 100%);">
                <div class="mb-3">
                    <span style="font-size: 3.5rem;">🎉</span>
                </div>
                <h1 class="h3 fw-bold mb-2">Pesanan On-Site Berhasil Terkirim!</h1>
                <p class="text-light opacity-90 mb-0 small">
                    Terima kasih telah memesan perlengkapan outdoor di Adventure Camp Rental.
                </p>
            </div>

            <div class="card-body p-4 p-md-5">
                <!-- Box Nomor Transaksi -->
                <div class="p-4 rounded-4 text-center mb-4" style="background-color: #f0f7f3; border: 2px dashed #52b788;">
                    <span class="text-muted small text-uppercase fw-bold letter-spacing-1 d-block mb-1">
                        Nomor Transaksi Pemesanan Anda:
                    </span>
                    <div class="display-6 fw-bold text-success font-monospace mb-2" style="letter-spacing: 1px;">
                        {{ $penyewaan->no_transaksi }}
                    </div>
                    <span class="badge bg-warning text-dark px-3 py-2 fs-6 rounded-pill">
                        ⏳ Menunggu Konfirmasi & Pemeriksaan Admin Kasir
                    </span>
                </div>

                <!-- Alert Instruksi Kasir -->
                <div class="alert alert-info border-0 rounded-4 p-3 p-md-4 mb-4 d-flex align-items-start gap-3">
                    <span class="fs-2" style="line-height:1;">💁‍♂️</span>
                    <div>
                        <h2 class="h6 fw-bold text-dark mb-1">Langkah Selanjutnya di Meja Kasir:</h2>
                        <ol class="mb-0 ps-3 small text-muted" style="line-height:1.6;">
                            <li>Tunjukkan <strong>Nomor Transaksi</strong> di atas kepada Admin / Kasir di counter depan Anda.</li>
                            <li>Admin akan memeriksa ketersediaan fisik alat dan menyetujui pesanan Anda di sistem kasir.</li>
                            <li>Lakukan pembayaran sesuai metode yang Anda pilih (<strong>{{ $penyewaan->pembayaran?->metode_pembayaran ?? 'Cash' }}</strong>) dan periksa kelengkapan alat bersama petugas.</li>
                        </ol>
                    </div>
                </div>

                <!-- Ringkasan Rincian Pesanan -->
                <h3 class="h6 fw-bold text-dark mb-3">Ringkasan Data Pemesanan:</h3>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <tbody>
                            <tr>
                                <th class="bg-light text-muted small" style="width: 35%;">Nama Pemesan</th>
                                <td class="fw-bold text-dark">{{ $penyewaan->pelanggan?->nama_pelanggan }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted small">No. WhatsApp / Telepon</th>
                                <td class="font-monospace">{{ $penyewaan->pelanggan?->nomor_telepon }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted small">Alamat Domisili</th>
                                <td>{{ $penyewaan->pelanggan?->alamat }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted small">Periode Waktu Sewa</th>
                                <td>
                                    <strong>{{ \Carbon\Carbon::parse($penyewaan->tanggal_sewa)->format('d M Y') }}</strong> s/d 
                                    <strong>{{ \Carbon\Carbon::parse($penyewaan->tanggal_kembali)->format('d M Y') }}</strong>
                                    <span class="badge bg-secondary ms-2 font-monospace">{{ $penyewaan->durasi_hari }} Hari</span>
                                </td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted small">Metode Pembayaran</th>
                                <td><span class="badge bg-light text-dark border">{{ $penyewaan->pembayaran?->metode_pembayaran }}</span></td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted small">Total Biaya Sewa</th>
                                <td class="fs-5 fw-bold text-success font-monospace">
                                    Rp {{ number_format($penyewaan->total_biaya, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Daftar Barang Sewa -->
                <h3 class="h6 fw-bold text-dark mb-2">Item Peralatan yang Dipesan:</h3>
                <div class="list-group rounded-3 mb-4">
                    @foreach($penyewaan->detailPenyewaan as $detail)
                        <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                            <div>
                                <strong class="text-dark small d-block">{{ $detail->alat?->nama_alat ?? $detail->kode_alat }}</strong>
                                <span class="text-muted" style="font-size:0.75rem;">
                                    Tarif: Rp {{ number_format($detail->tarif_sewa, 0, ',', '.') }}/hari &bull; Jumlah: {{ $detail->jumlah }} unit
                                </span>
                            </div>
                            <span class="fw-bold text-dark font-monospace small">
                                Rp {{ number_format($detail->hitungSubtotal($penyewaan->durasi_hari), 0, ',', '.') }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <!-- Tombol Aksi Selesai / Pesan Baru -->
                <div class="d-grid gap-2">
                    <form action="{{ route('onsite.mulai') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-dark btn-lg w-100 fw-bold py-3 kiosk-btn-touch shadow-sm">
                            <span>✨ Selesai / Pesanan Baru</span>
                        </button>
                    </form>
                    <a href="{{ route('onsite.landing') }}" class="btn btn-outline-secondary py-2 kiosk-btn-touch">
                        Kembali ke Halaman Awal
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

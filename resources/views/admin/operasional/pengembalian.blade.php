@extends('layouts.app')

@section('title', 'Daftar Pengembalian Alat Camping — Admin')
@section('page-title', 'Pengembalian & Pemeriksaan Alat')

@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard Admin</a></li>
            <li class="breadcrumb-item active" aria-current="page">Pengembalian Alat</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h4 font-weight-bold mb-1 text-dark">Daftar Pesanan Sedang Disewa</h2>
            <p class="text-muted small mb-0">Pesanan yang saat ini sedang aktif disewa dan perlu dikembalikan serta diperiksa kondisi fisiknya.</p>
        </div>
        <div>
            <a href="{{ route('admin.operasional.pengambilan') }}" class="btn btn-outline-secondary btn-sm">
                &larr; Lihat Antrean Penyerahan
            </a>
        </div>
    </div>
</div>

<!-- Informasi SOP Pengembalian & Denda -->
<div class="alert alert-warning border-warning d-flex align-items-start gap-2 mb-4 p-3" style="border-radius: 10px;">
    <span style="font-size: 1.25rem;">🔍</span>
    <div>
        <strong class="d-block mb-1 text-dark">SOP Pemeriksaan Fisik Pengembalian & Kebijakan Denda:</strong>
        <p class="mb-0 small text-dark">
            1. Periksa kelengkapan dan kondisi fisik setiap alat. Hanya alat berkondisi <strong>Baik</strong> yang akan dikembalikan ke stok siap sewa.<br>
            2. Alat <strong>Rusak</strong> atau butuh perbaikan <em>tidak akan dikembalikan</em> ke stok siap sewa.<br>
            3. Denda keterlambatan dihitung transparan berdasarkan selisih hari dikali tarif sewa harian alat.<br>
            4. Denda kerusakan wajib disertai rincian dan alasan yang jelas.
        </p>
    </div>
</div>

<!-- Tabel Pesanan Sedang Disewa -->
<div class="ac-card">
    <div class="ac-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h3 class="ac-card-title">Penyewaan Aktif ({{ $sewaAktifList->count() }} Transaksi)</h3>
            <small class="text-muted">Memantau alat yang sedang dibawa oleh penyewa</small>
        </div>
        <form action="{{ route('admin.operasional.pengembalian') }}" method="GET" class="d-flex align-items-center gap-2">
            <div class="input-group input-group-sm" style="max-width: 260px;">
                <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Cari No. TRX / Pelanggan...">
                <button class="btn btn-outline-secondary" type="submit">Cari</button>
                @if($search !== '')
                    <a href="{{ route('admin.operasional.pengembalian') }}" class="btn btn-outline-danger" title="Reset">&times;</a>
                @endif
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="table-pengembalian-admin">
            <thead class="table-light small">
                <tr>
                    <th>No. Transaksi</th>
                    <th>Pelanggan</th>
                    <th>Tgl Pengambilan</th>
                    <th>Batas Kembali</th>
                    <th>Status Waktu</th>
                    <th>Item Alat</th>
                    <th class="text-center" style="min-width: 150px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sewaAktifList as $trx)
                    @php
                        $tglKembali = \Carbon\Carbon::parse($trx->tanggal_kembali)->startOfDay();
                        $hariIni = \Carbon\Carbon::today();
                        $terlambatHari = $hariIni->gt($tglKembali) ? $tglKembali->diffInDays($hariIni) : 0;
                    @endphp
                    <tr>
                        <td>
                            <strong class="text-dark font-monospace d-block">{{ $trx->no_transaksi }}</strong>
                            <small class="text-muted">{{ $trx->pembayaran?->no_pembayaran ?? '-' }}</small>
                        </td>
                        <td>
                            <div class="font-weight-bold text-dark">{{ $trx->pelanggan->nama_pelanggan ?? 'Pelanggan #' . $trx->id_pelanggan }}</div>
                            <small class="text-muted">{{ $trx->pelanggan->nomor_telepon ?? '-' }}</small>
                        </td>
                        <td>
                            <span class="small text-dark d-block">
                                {{ $trx->pengambilan ? \Carbon\Carbon::parse($trx->pengambilan->tanggal_pengambilan)->format('d/m/Y') : \Carbon\Carbon::parse($trx->tanggal_sewa)->format('d/m/Y') }}
                            </span>
                            @if($trx->pengambilan)
                                <small class="text-muted">Penerima: {{ $trx->pengambilan->nama_penerima }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="small text-dark font-weight-bold d-block">
                                {{ $tglKembali->format('d/m/Y') }}
                            </span>
                            <small class="text-muted">Durasi: {{ $trx->durasi_hari }} Hari</small>
                        </td>
                        <td>
                            @if($terlambatHari > 0)
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 font-weight-bold">
                                    ⚠️ Terlambat {{ $terlambatHari }} Hari
                                </span>
                            @elseif($hariIni->equalTo($tglKembali))
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 font-weight-bold">
                                    ⏳ Jatuh Tempo Hari Ini
                                </span>
                            @else
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    ✓ Aktif (Sisa {{ $hariIni->diffInDays($tglKembali) }} Hari)
                                </span>
                            @endif
                        </td>
                        <td>
                            <ul class="list-unstyled mb-0 small">
                                @foreach($trx->detailPenyewaan as $detail)
                                    <li>
                                        • <strong>{{ $detail->alat->nama_alat ?? $detail->kode_alat }}</strong>: 
                                        <span class="badge bg-light text-dark border ms-1">
                                            {{ $detail->jumlah_diserahkan > 0 ? $detail->jumlah_diserahkan : $detail->jumlah }} unit
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('admin.operasional.form-pengembalian', $trx->no_transaksi) }}" 
                               class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-sm px-3"
                               id="btn-kembalikan-{{ $trx->no_transaksi }}">
                                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                </svg>
                                Periksa & Kembalikan
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <div class="mb-2" style="font-size: 2rem;">⛺</div>
                            <h5 class="fw-bold mb-1">Tidak Ada Pesanan Sedang Disewa</h5>
                            <p class="small mb-0">Semua alat sewaan telah dikembalikan atau belum ada pesanan yang diserahkan.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

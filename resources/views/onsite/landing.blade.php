@extends('layouts.onsite')

@section('title', 'Selamat Datang di Adventure Camp Rental')

@section('content')
<div class="row justify-content-center align-items-center py-md-4">
    <div class="col-12 col-lg-10 col-xl-9">
        <div class="card border-0 shadow-lg overflow-hidden" style="border-radius: 20px;">
            <!-- Hero Top Banner -->
            <div class="p-4 p-md-5 text-center text-white" style="background: linear-gradient(135deg, #0d281e 0%, #1b4332 50%, #2d6a4f 100%);">
                <div class="mb-3">
                    <span style="font-size: 4rem; filter: drop-shadow(0 4px 10px rgba(0,0,0,0.3));">⛺</span>
                </div>
                <h1 class="display-5 fw-bold mb-3" style="letter-spacing: -0.5px;">
                    Selamat Datang di Adventure Camp Rental
                </h1>
                <p class="lead mb-4 mx-auto text-light opacity-90" style="max-width: 650px; font-size: 1.15rem;">
                    Mode pemesanan langsung untuk pelanggan di toko. Pilih alat camping impian Anda lewat tablet ini tanpa perlu registrasi akun pengguna terlebih dahulu.
                </p>

                <!-- Tombol Mulai Pesanan -->
                <form action="{{ route('onsite.mulai') }}" method="POST" class="d-inline-block">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-lg px-5 py-3 fw-bold shadow-lg kiosk-btn-touch text-dark" style="font-size: 1.25rem; border-radius: 50px; transition: transform 0.2s ease;">
                        <span class="fs-4">🚀</span>
                        <span>Mulai Pesanan</span>
                    </button>
                </form>

                @if(isset($cartCount) && $cartCount > 0)
                    <div class="mt-3">
                        <a href="{{ route('onsite.katalog') }}" class="btn btn-sm btn-outline-light rounded-pill px-3 py-1">
                            Lanjutkan pesanan yang belum selesai ({{ $cartCount }} unit di keranjang) &rarr;
                        </a>
                    </div>
                @endif
            </div>

            <!-- Features Highlights -->
            <div class="card-body p-4 p-md-5 bg-white">
                <h2 class="h5 fw-bold text-center text-dark mb-4">Langkah Mudah Sewa di Meja Kasir:</h2>
                
                <div class="row g-4 text-center">
                    <div class="col-12 col-md-4">
                        <div class="p-3 rounded-4 h-100" style="background-color: #f8faf9; border: 1px solid #e9ecef;">
                            <div class="fs-1 mb-2">🏕️</div>
                            <h3 class="h6 fw-bold text-dark">1. Pilih Peralatan</h3>
                            <p class="small text-muted mb-0">
                                Jelajahi katalog lengkap dengan ketersediaan stok fisik dan tarif harian transparan.
                            </p>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="p-3 rounded-4 h-100" style="background-color: #f8faf9; border: 1px solid #e9ecef;">
                            <div class="fs-1 mb-2">📅</div>
                            <h3 class="h6 fw-bold text-dark">2. Tentukan Tanggal & Identitas</h3>
                            <p class="small text-muted mb-0">
                                Pilih periode sewa untuk seluruh barang sekaligus, lalu isi nama, telepon, dan alamat Anda.
                            </p>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="p-3 rounded-4 h-100" style="background-color: #f8faf9; border: 1px solid #e9ecef;">
                            <div class="fs-1 mb-2">🤝</div>
                            <h3 class="h6 fw-bold text-dark">3. Konfirmasi di Kasir</h3>
                            <p class="small text-muted mb-0">
                                Dapatkan nomor transaksi dan tunjukkan ke kasir di depan Anda untuk pemeriksaan & serah terima alat.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4 pt-3 border-top">
                    <span class="text-muted small">
                        Pemesanan online dari rumah? Hubungi kasir untuk info reservasi website online.
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

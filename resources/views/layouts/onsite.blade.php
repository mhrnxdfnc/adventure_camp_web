<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Pemesanan On-Site') — {{ config('app.name', 'Adventure Camp Rental') }}</title>

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    
    <!-- Adventure Camp Custom Theme -->
    <link rel="stylesheet" href="{{ asset('css/adventure-theme.css') }}">

    <style>
        .kiosk-body {
            background: linear-gradient(180deg, #f0f7f3 0%, #e8f3ed 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            user-select: none;
            -webkit-user-select: none;
        }
        .kiosk-header {
            background-color: #0d281e;
            color: #ffffff;
            box-shadow: 0 4px 16px rgba(13, 40, 30, 0.15);
            position: sticky;
            top: 0;
            z-index: 1020;
        }
        .kiosk-brand {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #ffffff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .kiosk-badge {
            background-color: #52b788;
            color: #0d281e;
            font-weight: 700;
            font-size: 0.72rem;
            padding: 0.25rem 0.6rem;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .kiosk-btn-touch {
            min-height: 48px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.6rem 1.25rem;
            touch-action: manipulation;
        }
        .kiosk-cart-pill {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: #ffffff;
            transition: all 0.2s ease;
        }
        .kiosk-cart-pill:hover, .kiosk-cart-pill:focus {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }
        .kiosk-container {
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
            padding: 1.5rem 1rem 3rem;
            flex-grow: 1;
        }
        .kiosk-footer {
            background-color: #ffffff;
            border-top: 1px solid #d8f3dc;
            padding: 1rem 0;
            font-size: 0.85rem;
            color: #4b5563;
        }
    </style>

    @stack('styles')
</head>
<body class="kiosk-body">
    <!-- Header Tablet Kiosk -->
    <header class="kiosk-header py-2 px-3 px-md-4">
        <div class="container-fluid d-flex align-items-center justify-content-between">
            <a href="{{ route('onsite.landing') }}" class="kiosk-brand">
                <span style="font-size: 1.6rem;">⛺</span>
                <div>
                    <div>Adventure Camp</div>
                    <div style="font-size: 0.68rem; font-weight: 400; color: #95d5b2; line-height: 1;">Rental Perlengkapan Outdoor</div>
                </div>
                <span class="kiosk-badge d-none d-sm-inline-block">Kiosk Kasir</span>
            </a>

            <div class="d-flex align-items-center gap-2">
                @if(!request()->routeIs('onsite.landing'))
                    <a href="{{ route('onsite.katalog') }}" class="btn btn-sm btn-outline-light d-inline-flex align-items-center gap-1 {{ request()->routeIs('onsite.katalog') ? 'active bg-light text-dark' : '' }}">
                        <span>🏕️ Katalog</span>
                    </a>

                    <a href="{{ route('onsite.keranjang') }}" class="btn btn-sm kiosk-cart-pill position-relative d-inline-flex align-items-center gap-1">
                        <span>🛒 Keranjang</span>
                        <span class="badge rounded-pill bg-warning text-dark font-weight-bold" id="kioskCartBadge">
                            {{ \App\Http\Controllers\OnsiteKioskController::countCart() }}
                        </span>
                    </a>

                    <form action="{{ route('onsite.batal') }}" method="POST" class="d-inline" onsubmit="return confirm('Batalkan pesanan saat ini? Seluruh pilihan alat di keranjang dan data sementara akan dibersihkan.');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger text-light border-danger-subtle d-inline-flex align-items-center gap-1" title="Batalkan & Reset Kiosk">
                            <span>🔄 Batal / Baru</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </header>

    <!-- Content Workspace -->
    <main class="kiosk-container">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4 d-flex align-items-center gap-2" role="alert">
                <span class="fs-5">✅</span>
                <div class="flex-grow-1">{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show shadow-sm mb-4 d-flex align-items-center gap-2" role="alert">
                <span class="fs-5">ℹ️</span>
                <div class="flex-grow-1">{{ session('info') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4 d-flex align-items-center gap-2" role="alert">
                <span class="fs-5">⚠️</span>
                <div class="flex-grow-1">{{ session('error') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
                <div class="fw-bold mb-1">Periksa kembali data yang dimasukkan:</div>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer Tablet Kiosk -->
    <footer class="kiosk-footer text-center">
        <div class="container">
            <span class="fw-semibold text-dark">Mode Pemesanan Langsung di Toko (On-Site Tablet)</span>
            <span class="mx-2 text-muted">&bull;</span>
            <span>Butuh bantuan atau rekomendasi alat? Kasir kami siap membantu Anda di meja counter.</span>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    
    <script>
        // Pastikan tidak ada data sensitif yang tersisa di penyimpanan browser tablet bersama
        try {
            sessionStorage.clear();
            localStorage.removeItem('onsite_customer_draft');
        } catch(e) {}

        // Cegah tampilan usang dari bfcache jika tombol Back ditekan pada tablet
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>

    @stack('scripts')
</body>
</html>

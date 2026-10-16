<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koneksi Database Tidak Tersedia — {{ config('app.name', 'Adventure Camp Rental') }}</title>

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <!-- Adventure Camp Custom Theme -->
    <link rel="stylesheet" href="{{ asset('css/adventure-theme.css') }}">
</head>
<body>
    <div class="ac-auth-wrap">
        <div class="ac-auth-card text-center py-4">
            <div class="ac-auth-header mb-3">
                <div class="ac-auth-logo">
                    ⛺
                </div>
                <h1 class="ac-auth-title">Adventure Camp</h1>
                <p class="ac-auth-subtitle">Sistem Informasi Penyewaan Alat Camping</p>
            </div>

            <div class="mb-3">
                <span class="d-inline-flex align-items-center justify-content-center bg-danger-subtle text-danger rounded-circle" style="width: 64px; height: 64px; font-size: 1.8rem;">
                    ⚠️
                </span>
            </div>

            <span class="badge bg-danger mb-2 font-monospace">Kode Error: ERR_DB_CONN</span>

            <h4 class="text-danger fw-bold mb-2">Layanan Database Tidak Tersedia</h4>
            <p class="text-muted mx-auto mb-4" style="max-width: 360px; font-size: 0.9rem;">
                Sistem saat ini sedang tidak dapat terhubung ke server database. Silakan pastikan service MySQL Laragon aktif pada port 3306 atau coba beberapa saat lagi.
            </p>

            <div class="d-grid gap-2">
                <a href="{{ url()->current() }}" class="btn btn-ac-primary">
                    🔄 Coba Muat Ulang
                </a>
                <a href="{{ url('/') }}" class="btn btn-outline-secondary btn-sm">
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>

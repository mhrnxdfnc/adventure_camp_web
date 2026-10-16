<aside class="ac-sidebar" id="acSidebar">
    <!-- Brand Header -->
    <div class="ac-sidebar-header">
        <a href="{{ url('/') }}" class="ac-brand">
            <span class="ac-brand-icon">⛺</span>
            <div class="ac-brand-text">
                Adventure Camp
                <small>Rental Management</small>
            </div>
        </a>
    </div>

    <!-- Navigation Menu Items -->
    <nav class="ac-sidebar-menu">
        <div class="ac-menu-category">Menu Utama</div>
        
        @auth
            @if(Auth::user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="ac-menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <svg class="ac-menu-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span>Dashboard Admin</span>
                </a>
            @elseif(Auth::user()->isPemilik())
                <a href="{{ route('pemilik.dashboard') }}" class="ac-menu-item {{ request()->routeIs('pemilik.dashboard') ? 'active' : '' }}">
                    <svg class="ac-menu-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span>Dashboard Pemilik</span>
                </a>
                <a href="{{ route('pemilik.transaksi.index') }}" class="ac-menu-item {{ request()->routeIs('pemilik.transaksi.*') ? 'active' : '' }}">
                    <svg class="ac-menu-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    <span>Riwayat Transaksi</span>
                </a>
            @else
                <a href="{{ route('pengguna.dashboard') }}" class="ac-menu-item {{ request()->routeIs('pengguna.dashboard') ? 'active' : '' }}">
                    <svg class="ac-menu-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span>Dashboard Pengguna</span>
                </a>
            @endif
        @else
            <a href="{{ route('login') }}" class="ac-menu-item {{ request()->routeIs('login') ? 'active' : '' }}">
                <svg class="ac-menu-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                </svg>
                <span>Halaman Login</span>
            </a>
            <a href="{{ route('register') }}" class="ac-menu-item {{ request()->routeIs('register') ? 'active' : '' }}">
                <svg class="ac-menu-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
                <span>Daftar Pengguna</span>
            </a>
        @endauth

        <!-- Modul Operasional Sesuai Hak Akses -->
        @auth
            @if(Auth::user()->isAdmin())
                <div class="ac-menu-category">Modul Operasional</div>

                <a href="{{ route('admin.alat.index') }}" class="ac-menu-item {{ request()->routeIs('admin.alat.*') ? 'active' : '' }}">
                    <svg class="ac-menu-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    <span>Kelola Data Alat</span>
                </a>

                <a href="{{ route('admin.inventaris.index') }}" class="ac-menu-item {{ request()->routeIs('admin.inventaris.*') ? 'active' : '' }}">
                    <svg class="ac-menu-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <span>Inventaris & Stok</span>
                </a>

                <a href="{{ route('admin.pembayaran.index') }}" class="ac-menu-item {{ request()->routeIs('admin.pembayaran.*') ? 'active' : '' }}">
                    <svg class="ac-menu-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    <span>Verifikasi Pembayaran</span>
                </a>

                <a href="{{ route('admin.operasional.pengambilan') }}" class="ac-menu-item {{ request()->routeIs('admin.operasional.pengambilan*') ? 'active' : '' }}">
                    <svg class="ac-menu-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <span>Penyerahan Alat</span>
                </a>

                <a href="{{ route('admin.operasional.pengembalian') }}" class="ac-menu-item {{ request()->routeIs('admin.operasional.pengembalian*') || request()->routeIs('admin.operasional.form-pengembalian*') ? 'active' : '' }}">
                    <svg class="ac-menu-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                    </svg>
                    <span>Pengembalian & Denda</span>
                </a>

                <a href="{{ route('admin.logs.index') }}" class="ac-menu-item {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                    <svg class="ac-menu-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>Audit Trail Log</span>
                </a>
            @elseif(Auth::user()->isPengguna())
                <div class="ac-menu-category">Layanan Sewa</div>

                <a href="{{ route('pengguna.katalog.index') }}" class="ac-menu-item {{ request()->routeIs('pengguna.katalog.*') ? 'active' : '' }}">
                    <svg class="ac-menu-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    <span>Katalog Alat Camping</span>
                </a>
            @endif
        @else
            <div class="ac-menu-category">Katalog</div>
            <a href="{{ route('pengguna.katalog.index') }}" class="ac-menu-item {{ request()->routeIs('pengguna.katalog.*') ? 'active' : '' }}">
                <svg class="ac-menu-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <span>Katalog Alat Camping</span>
            </a>
        @endauth
    </nav>

    <!-- Sidebar Footer -->
    <div class="ac-sidebar-footer">
        @auth
            <div class="ac-user-card">
                <div class="ac-avatar">
                    {{ strtoupper(substr(Auth::user()->nama_user, 0, 2)) }}
                </div>
                <div class="ac-user-info">
                    <b>{{ Auth::user()->nama_user }}</b>
                    <small>{{ Auth::user()->normalized_role }}</small>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="ms-auto d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-link text-white p-0" title="Keluar dari Sistem">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                    </button>
                </form>
            </div>
        @else
            <div class="ac-user-card">
                <div class="ac-avatar">AC</div>
                <div class="ac-user-info">
                    <b>Pengunjung</b>
                    <small>Belum Masuk</small>
                </div>
                <a href="{{ route('login') }}" class="btn btn-sm btn-link text-white p-0 ms-auto" title="Masuk">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                </a>
            </div>
        @endauth
    </div>
</aside>

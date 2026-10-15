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
        <div class="ac-menu-category">Tampilan Role</div>
        
        <a href="{{ url('/admin/dashboard') }}" class="ac-menu-item {{ request()->is('admin*') ? 'active' : '' }}">
            <svg class="ac-menu-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            <span>Dashboard Admin</span>
        </a>

        <a href="{{ url('/pengguna/dashboard') }}" class="ac-menu-item {{ request()->is('pengguna*') ? 'active' : '' }}">
            <svg class="ac-menu-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            <span>Dashboard Pengguna</span>
        </a>

        <a href="{{ url('/pemilik/dashboard') }}" class="ac-menu-item {{ request()->is('pemilik*') ? 'active' : '' }}">
            <svg class="ac-menu-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            <span>Dashboard Pemilik</span>
        </a>

        <div class="ac-menu-category">Fondasi Antarmuka</div>

        <a href="{{ url('/ui-showcase') }}" class="ac-menu-item {{ request()->is('ui-showcase*') ? 'active' : '' }}">
            <svg class="ac-menu-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
            </svg>
            <span>Showcase Komponen UI</span>
        </a>

        <a href="{{ url('/login') }}" class="ac-menu-item {{ request()->is('login*') ? 'active' : '' }}">
            <svg class="ac-menu-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
            </svg>
            <span>Halaman Login</span>
        </a>

        <!-- Struktur Modul Operasional (Disiapkan untuk integrasi autentikasi & otorisasi) -->
        <div class="ac-menu-category">Modul Operasional</div>

        <div class="ac-menu-item text-muted" style="opacity: 0.6; cursor: not-allowed;" title="Akan aktif setelah modul katalog selesai">
            <svg class="ac-menu-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
            <span>Katalog Alat</span>
            <small class="badge bg-secondary ms-auto" style="font-size: 0.65rem;">Tahap 2</small>
        </div>

        <div class="ac-menu-item text-muted" style="opacity: 0.6; cursor: not-allowed;" title="Akan aktif setelah modul transaksi selesai">
            <svg class="ac-menu-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            <span>Penyewaan</span>
            <small class="badge bg-secondary ms-auto" style="font-size: 0.65rem;">Tahap 2</small>
        </div>

        <div class="ac-menu-item text-muted" style="opacity: 0.6; cursor: not-allowed;" title="Akan aktif setelah modul pembayaran selesai">
            <svg class="ac-menu-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
            </svg>
            <span>Pembayaran</span>
            <small class="badge bg-secondary ms-auto" style="font-size: 0.65rem;">Tahap 2</small>
        </div>

        <div class="ac-menu-item text-muted" style="opacity: 0.6; cursor: not-allowed;" title="Akan aktif setelah modul pengembalian selesai">
            <svg class="ac-menu-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
            </svg>
            <span>Pengembalian & Denda</span>
            <small class="badge bg-secondary ms-auto" style="font-size: 0.65rem;">Tahap 2</small>
        </div>

        <div class="ac-menu-item text-muted" style="opacity: 0.6; cursor: not-allowed;" title="Akan aktif setelah modul inventaris selesai">
            <svg class="ac-menu-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
            <span>Inventaris</span>
            <small class="badge bg-secondary ms-auto" style="font-size: 0.65rem;">Tahap 2</small>
        </div>
    </nav>

    <!-- Sidebar Footer -->
    <div class="ac-sidebar-footer">
        <div class="ac-user-card">
            <div class="ac-avatar">AC</div>
            <div class="ac-user-info">
                <b>Pengguna Sistem</b>
                <small>Sesi Aktif</small>
            </div>
            <a href="{{ url('/login') }}" class="btn btn-sm btn-link text-white p-0 ms-auto" title="Keluar">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
            </a>
        </div>
    </div>
</aside>

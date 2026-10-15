<header class="ac-topbar">
    <div class="ac-topbar-left">
        <!-- Mobile Sidebar Toggle Hamburger Button -->
        <button class="btn btn-sm btn-ac-outline d-lg-none" type="button" id="acSidebarToggle" aria-label="Buka Menu">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>

        <h2 class="ac-topbar-title">@yield('page-title', 'Dashboard')</h2>
    </div>

    <div class="d-flex align-items-center gap-2">
        <a href="{{ url('/ui-showcase') }}" class="btn btn-sm btn-ac-outline d-none d-sm-inline-flex align-items-center gap-1">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
            </svg>
            Showcase UI
        </a>

        <!-- User Profile Dropdown Placeholder -->
        <div class="dropdown">
            <button class="btn btn-sm btn-ac-outline dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="ac-avatar" style="width:28px;height:28px;font-size:0.75rem;">AC</span>
                <span class="d-none d-md-inline font-weight-medium">Pengguna</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="border-radius:10px;">
                <li><h6 class="dropdown-header text-uppercase" style="font-size:0.7rem;letter-spacing:0.05em;">Peralihan Tampilan</h6></li>
                <li><a class="dropdown-item py-2" href="{{ url('/admin/dashboard') }}">Dashboard Admin</a></li>
                <li><a class="dropdown-item py-2" href="{{ url('/pengguna/dashboard') }}">Dashboard Pengguna</a></li>
                <li><a class="dropdown-item py-2" href="{{ url('/pemilik/dashboard') }}">Dashboard Pemilik</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item py-2 text-danger" href="{{ url('/login') }}">Keluar / Ganti Akun</a></li>
            </ul>
        </div>
    </div>
</header>

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

        @auth
            <!-- User Profile Dropdown -->
            <div class="dropdown">
                <button class="btn btn-sm btn-ac-outline dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="ac-avatar" style="width:28px;height:28px;font-size:0.75rem;">
                        {{ strtoupper(substr(Auth::user()->nama_user, 0, 2)) }}
                    </span>
                    <span class="d-none d-md-inline font-weight-medium">{{ Auth::user()->nama_user }}</span>
                    <span class="badge bg-success-subtle text-success small d-none d-lg-inline">{{ Auth::user()->normalized_role }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="border-radius:10px;">
                    <li><h6 class="dropdown-header text-uppercase" style="font-size:0.7rem;letter-spacing:0.05em;">Sesi Akun ({{ Auth::user()->normalized_role }})</h6></li>
                    @if(Auth::user()->isAdmin())
                        <li><a class="dropdown-item py-2" href="{{ route('admin.dashboard') }}">Dashboard Admin</a></li>
                    @elseif(Auth::user()->isPemilik())
                        <li><a class="dropdown-item py-2" href="{{ route('pemilik.dashboard') }}">Dashboard Pemilik</a></li>
                    @else
                        <li><a class="dropdown-item py-2" href="{{ route('pengguna.dashboard') }}">Dashboard Pengguna</a></li>
                    @endif
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="dropdown-item py-2 text-danger d-flex align-items-center gap-2">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                Keluar Sistem
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        @else
            <a href="{{ route('login') }}" class="btn btn-sm btn-ac-primary">Masuk</a>
            <a href="{{ route('register') }}" class="btn btn-sm btn-ac-outline d-none d-sm-inline-flex">Daftar</a>
        @endauth
    </div>
</header>

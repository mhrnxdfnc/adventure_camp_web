<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Masuk') — {{ config('app.name', 'Adventure Camp Rental') }}</title>

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    
    <!-- Adventure Camp Custom Theme -->
    <link rel="stylesheet" href="{{ asset('css/adventure-theme.css') }}">

    @stack('styles')
</head>
<body>
    <div class="ac-auth-wrap">
        <div class="ac-auth-card">
            <div class="ac-auth-header">
                <div class="ac-auth-logo">
                    ⛺
                </div>
                <h1 class="ac-auth-title">Adventure Camp</h1>
                <p class="ac-auth-subtitle">Sistem Informasi Penyewaan Alat Camping</p>
            </div>

            <!-- Global Flash Alerts -->
            @include('components.alerts')

            <!-- Form Content -->
            @yield('content')
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <!-- Adventure Camp Core UI JS -->
    <script src="{{ asset('js/adventure-ui.js') }}"></script>

    @stack('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name', 'Adventure Camp Rental') }}</title>

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    
    <!-- Adventure Camp Custom Theme -->
    <link rel="stylesheet" href="{{ asset('css/adventure-theme.css') }}">

    @stack('styles')
</head>
<body>
    <div class="ac-app-wrapper">
        <!-- Mobile Sidebar Backdrop -->
        <div class="ac-sidebar-backdrop" id="acSidebarBackdrop"></div>

        <!-- Reusable Sidebar -->
        @include('components.sidebar')

        <!-- Main Workspace Area -->
        <div class="ac-main-area">
            <!-- Reusable Top Navbar -->
            @include('components.navbar')

            <!-- Main Dynamic Content Container -->
            <main class="ac-content-container">
                <!-- Global Alerts Component -->
                @include('components.alerts')

                @yield('content')
            </main>

            <!-- Reusable Footer -->
            @include('components.footer')
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    
    <!-- Adventure Camp Core UI JS -->
    <script src="{{ asset('js/adventure-ui.js') }}"></script>

    @stack('scripts')
</body>
</html>

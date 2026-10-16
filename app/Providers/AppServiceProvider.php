<?php

namespace App\Providers;

use App\Http\Controllers\KeranjangController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Bagikan jumlah item keranjang belanja untuk navbar dan sidebar pengguna aktif
        View::composer(['components.navbar', 'components.sidebar'], function ($view) {
            $cartCount = 0;
            if (Auth::check() && Auth::user()->isPengguna()) {
                $cartCount = KeranjangController::count(Auth::id());
            }
            $view->with('cartCount', $cartCount);
        });
    }
}

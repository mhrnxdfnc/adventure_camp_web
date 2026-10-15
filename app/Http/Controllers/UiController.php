<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class UiController extends Controller
{
    /**
     * Halaman showcase komponen antarmuka
     */
    public function showcase(): View
    {
        return view('ui-showcase');
    }

    /**
     * Tampilan login mandiri
     */
    public function login(): View
    {
        return view('auth.login');
    }

    /**
     * Layout dashboard Admin
     */
    public function adminDashboard(): View
    {
        return view('admin.dashboard');
    }

    /**
     * Layout dashboard Pengguna / Pelanggan
     */
    public function penggunaDashboard(): View
    {
        return view('pengguna.dashboard');
    }

    /**
     * Layout dashboard Pemilik (Strictly Read-Only)
     */
    public function pemilikDashboard(): View
    {
        return view('pemilik.dashboard');
    }
}

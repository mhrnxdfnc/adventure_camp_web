<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminLogController extends Controller
{
    /**
     * Tampilkan riwayat audit trail log aktivitas sistem untuk Admin (Strictly Read-Only)
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $modul = trim((string) $request->query('modul', ''));
        $tanggalMulai = $request->query('tanggal_mulai');
        $tanggalSelesai = $request->query('tanggal_selesai');

        $query = ActivityLog::with('user');

        // Pencarian kata kunci pada aktivitas, modul, data_diproses, atau nama/username user
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('aktivitas', 'like', "%{$search}%")
                    ->orWhere('modul', 'like', "%{$search}%")
                    ->orWhere('data_diproses', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($sub) use ($search) {
                        $sub->where('nama_user', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        // Filter berdasarkan modul
        if ($modul !== '') {
            $query->where('modul', $modul);
        }

        // Filter rentang tanggal
        if (! empty($tanggalMulai)) {
            $query->whereDate('waktu', '>=', $tanggalMulai);
        }

        if (! empty($tanggalSelesai)) {
            $query->whereDate('waktu', '<=', $tanggalSelesai);
        }

        // Urutan kronologis terbaru ke terlama
        $logs = $query->orderBy('waktu', 'desc')
            ->orderBy('id_log', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Daftar modul untuk dropdown filter
        $availableModules = ActivityLog::select('modul')
            ->distinct()
            ->whereNotNull('modul')
            ->orderBy('modul')
            ->pluck('modul')
            ->toArray();

        // Metrik ringkasan audit trail
        $totalLogs = ActivityLog::count();
        $logsToday = ActivityLog::whereDate('waktu', Carbon::today('Asia/Jakarta'))->count();
        $totalUsersLogged = ActivityLog::distinct('id_user')->count('id_user');

        return view('admin.logs.index', [
            'logs' => $logs,
            'availableModules' => $availableModules,
            'filters' => [
                'search' => $search,
                'modul' => $modul,
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,
            ],
            'totalLogs' => $totalLogs,
            'logsToday' => $logsToday,
            'totalUsersLogged' => $totalUsersLogged,
        ]);
    }
}

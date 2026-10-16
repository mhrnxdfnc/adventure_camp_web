<div class="ac-card mb-4">
    <div class="ac-card-header d-flex justify-content-between align-items-center">
        <div>
            <h3 class="ac-card-title">📜 Riwayat Perubahan Stok Alat Ini</h3>
            <small class="text-muted">Audit trail perubahan kuantitas fisik dan mutasi stok</small>
        </div>
        <a href="{{ route('admin.inventaris.riwayat', ['kode_alat' => $alat->kode_alat]) }}" class="btn btn-sm btn-outline-secondary">
            Lihat Semua &rarr;
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>Waktu</th>
                    <th>Jenis Aktivitas</th>
                    <th class="text-center">Perubahan</th>
                    <th class="text-center">Stok Akhir</th>
                    <th>Alasan / Keterangan</th>
                    <th>Dicatat Oleh</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riwayatStok as $log)
                    <tr>
                        <td>
                            <span class="text-dark d-block">{{ $log->created_at->format('d/m/Y') }}</span>
                            <small class="text-muted">{{ $log->created_at->format('H:i:s') }} WIB</small>
                        </td>
                        <td>
                            @if(str_contains($log->jenis_perubahan, 'Penambahan') || str_contains($log->jenis_perubahan, 'Pembelian'))
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    {{ $log->jenis_perubahan }}
                                </span>
                            @elseif(str_contains($log->jenis_perubahan, 'Pengurangan') || str_contains($log->jenis_perubahan, 'Serah Terima'))
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                    {{ $log->jenis_perubahan }}
                                </span>
                            @else
                                <span class="badge bg-info-subtle text-info border border-info-subtle">
                                    {{ $log->jenis_perubahan }}
                                </span>
                            @endif
                        </td>
                        <td class="text-center font-monospace fw-bold">
                            @if($log->jumlah_perubahan > 0)
                                <span class="text-success">+{{ $log->jumlah_perubahan }}</span>
                            @else
                                <span class="text-danger">{{ $log->jumlah_perubahan }}</span>
                            @endif
                        </td>
                        <td class="text-center font-monospace">
                            <span class="badge bg-light text-dark border">{{ $log->stok_sesudah }} unit</span>
                        </td>
                        <td>
                            <span class="text-dark">{{ $log->alasan }}</span>
                        </td>
                        <td>
                            <strong class="text-dark d-block">{{ $log->user->nama_user ?? 'Admin #' . $log->id_user }}</strong>
                            <small class="text-muted">{{ $log->user->normalized_role ?? 'Admin' }}</small>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <em>Belum ada catatan riwayat perubahan stok untuk alat ini.</em>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

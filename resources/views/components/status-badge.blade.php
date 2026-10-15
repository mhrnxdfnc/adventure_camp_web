@props(['status' => '—'])

@php
    $s = trim((string) $status);
    $lower = strtolower($s);
    $type = 'secondary';
    $label = $s ?: '—';

    // Status mapping
    if (in_array($lower, ['selesai', 'lunas', 'baik', 'aktif', 'terverifikasi'])) {
        $type = 'success';
    } elseif (in_array($lower, ['menunggu konfirmasi', 'menunggu pembayaran', 'menunggu verifikasi', 'perlu perbaikan', 'berjalan'])) {
        $type = 'warning';
    } elseif (in_array($lower, ['disetujui', 'siap diambil', 'sedang disewa', 'diproses'])) {
        $type = 'info';
    } elseif (in_array($lower, ['ditolak', 'dibatalkan', 'rusak', 'belum lunas', 'bermasalah', 'tidak aktif'])) {
        $type = 'danger';
    } elseif (in_array($lower, ['belum dibayar', 'draft'])) {
        $type = 'secondary';
    }
@endphp

<span {{ $attributes->merge(['class' => 'ac-badge ac-badge-' . $type]) }}>
    <span class="ac-badge-dot"></span>
    <span>{{ $label }}</span>
</span>

@props([
    'status' => '—',
    'visualOnly' => false,
    'showText' => true,
])

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
    } elseif (in_array($lower, ['ditolak', 'dibatalkan', 'rusak', 'belum lunas', 'bermasalah', 'tidak aktif', 'nonaktif'])) {
        $type = 'danger';
    } elseif (in_array($lower, ['belum dibayar', 'draft'])) {
        $type = 'secondary';
    }

    $isVisualOnly = $visualOnly || ! $showText;
@endphp

<span {{ $attributes->merge(['class' => 'ac-badge ac-badge-' . $type . ($isVisualOnly ? ' ac-badge-visual-only' : '')]) }} title="{{ $label }}" aria-label="{{ $label }}" data-status="{{ $s }}">
    <span class="ac-badge-dot"></span>
    @unless($isVisualOnly)
        <span>{{ $label }}</span>
    @endunless
</span>

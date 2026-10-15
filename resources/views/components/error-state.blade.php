@props([
    'title' => 'Terjadi Kesalahan',
    'message' => 'Sistem tidak dapat memproses permintaan saat ini. Silakan coba beberapa saat lagi.',
    'code' => null,
    'retryUrl' => null
])

<div class="ac-card border-danger my-3">
    <div class="ac-card-body text-center py-5">
        <div class="mb-3">
            <span class="d-inline-flex align-items-center justify-content-center bg-danger-subtle text-danger rounded-circle" style="width: 60px; height: 60px; font-size: 1.5rem;">
                ⚠️
            </span>
        </div>

        @if ($code)
            <span class="badge bg-danger mb-2">Kode Error: {{ $code }}</span>
        @endif

        <h4 class="text-danger font-weight-bold mb-2">{{ $title }}</h4>
        <p class="text-muted mx-auto mb-4" style="max-width: 460px;">{{ $message }}</p>

        @if ($retryUrl)
            <a href="{{ $retryUrl }}" class="btn btn-sm btn-outline-danger px-3">
                Coba Muat Ulang
            </a>
        @endif
    </div>
</div>

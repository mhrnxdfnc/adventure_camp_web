@props([
    'title' => 'Belum Ada Data',
    'message' => 'Saat ini belum ada data yang tersedia untuk ditampilkan.',
    'actionText' => null,
    'actionUrl' => null,
    'icon' => '⛺'
])

<div class="ac-empty-state my-3">
    <div class="ac-empty-icon">
        {{ $icon }}
    </div>
    <h4 class="ac-empty-title">{{ $title }}</h4>
    <p class="ac-empty-text">{{ $message }}</p>

    @if ($actionText && $actionUrl)
        <a href="{{ $actionUrl }}" class="btn btn-ac-primary">
            {{ $actionText }}
        </a>
    @endif
</div>

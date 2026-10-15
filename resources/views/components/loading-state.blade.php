@props([
    'label' => 'Memuat data...',
    'rows' => 3
])

<div class="ac-card p-4 my-3">
    <div class="d-flex align-items-center gap-2 mb-3 text-muted">
        <div class="spinner-border spinner-border-sm text-success" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <span class="small font-weight-medium">{{ $label }}</span>
    </div>

    @for ($i = 0; $i < $rows; $i++)
        <div class="mb-3">
            <div class="ac-skeleton ac-skeleton-title" style="width: {{ rand(30, 60) }}%;"></div>
            <div class="ac-skeleton ac-skeleton-text" style="width: 100%;"></div>
            <div class="ac-skeleton ac-skeleton-text" style="width: {{ rand(70, 90) }}%;"></div>
        </div>
    @endfor
</div>

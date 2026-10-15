@props([
    'title' => null,
    'subtitle' => null,
    'searchId' => null,
    'searchPlaceholder' => 'Cari data...',
])

<div class="ac-card">
    @if ($title || isset($actions) || $searchId)
        <div class="ac-card-header flex-wrap gap-2">
            <div>
                @if ($title)
                    <h3 class="ac-card-title">{{ $title }}</h3>
                @endif
                @if ($subtitle)
                    <small class="text-muted d-block mt-1">{{ $subtitle }}</small>
                @endif
            </div>

            <div class="d-flex align-items-center gap-2 ms-auto flex-wrap">
                @if ($searchId)
                    <div class="input-group input-group-sm" style="max-width: 240px;">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </span>
                        <input type="text" id="{{ $searchId }}" class="form-control form-control-sm border-start-0" placeholder="{{ $searchPlaceholder }}" aria-label="{{ $searchPlaceholder }}">
                    </div>
                @endif

                @if (isset($actions))
                    {{ $actions }}
                @endif
            </div>
        </div>
    @endif

    <div class="ac-table-responsive">
        <table class="ac-table table-hover">
            {{ $slot }}
        </table>
    </div>

    @if (isset($pagination))
        <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
            {{ $pagination }}
        </div>
    @endif
</div>

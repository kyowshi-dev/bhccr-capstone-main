@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'actions' => null,
])

<div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
    <div class="flex items-start gap-3">
        @if ($icon)
            <span class="mt-0.5 header-chip">
                <i class="{{ $icon }} text-lg" aria-hidden="true"></i>
            </span>
        @endif
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl text-ink">{{ $title ?? $slot }}</h1>
            @if ($subtitle)
                <p class="text-sm mt-1 text-ink-muted">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
    @if ($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endif
</div>

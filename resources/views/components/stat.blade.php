@props(['label'])

<div>
    <p class="text-[10px] uppercase tracking-wide font-semibold" style="color: var(--ink-muted);">{{ $label }}</p>
    <div class="mt-0.5 text-sm font-semibold" style="color: var(--ink);">{!! $slot !!}</div>
</div>

@props([
    'icon' => 'fa-solid fa-inbox',
    'title' => 'Nothing here yet',
    'description' => null,
])

<div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-border bg-surface px-6 py-10 text-center">
    <span class="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-full bg-teal-soft text-primary">
        <i class="{{ $icon }} text-xl" aria-hidden="true"></i>
    </span>
    <h3 class="font-display font-semibold text-ink">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-ink-muted">{{ $description }}</p>
    @endif
    @if (!$slot->isEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>

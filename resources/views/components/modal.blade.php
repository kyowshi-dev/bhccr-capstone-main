@props([
    'id' => null,
    'title' => null,
    'maxWidth' => 'max-w-2xl',
    'closeOnBackdrop' => true,
])

@php
    $modalId = $id ?: 'modal-' . \Illuminate\Support\Str::random(6);
@endphp

<div
    x-data="{ open: false }"
    x-init="$watch('open', (v) => { document.body.classList.toggle('overflow-hidden', v); if (v) { const f = $refs.panel.querySelector('[data-autofocus], input, select, textarea, button'); f?.focus(); } })"
    x-on:keydown.escape.window="open = false"
    @if ($closeOnBackdrop) x-on:click.outside="open = false" @endif
    role="dialog"
    aria-modal="true"
    :aria-hidden="!open"
    {{ $attributes }}
>
    @if (isset($trigger) && !$trigger->isEmpty())
        <div @click="open = true">{{ $trigger }}</div>
    @else
        {{ $slot }}
    @endif

    <template x-teleport="body">
        <div x-show="open"
             x-transition.opacity.duration.200ms
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="display: none;">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
            <div x-ref="panel"
                 x-show="open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative w-full {{ $maxWidth }} max-h-[90vh] overflow-y-auto rounded-2xl border border-border bg-surface-elevated shadow-lg">
                @if ($title)
                    <div class="flex items-center justify-between border-b border-border px-6 py-4">
                        <h3 class="font-display font-semibold text-lg text-ink">{{ $title }}</h3>
                        <button type="button" @click="open = false" aria-label="Close" class="rounded-lg p-1.5 text-ink-muted transition-colors hover:bg-black/5">
                            <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
                        </button>
                    </div>
                @endif
                <div class="p-6">
                    {{ $content }}
                </div>
                @if (isset($footer) && !$footer->isEmpty())
                    <div class="flex flex-wrap items-center justify-end gap-2 border-t border-border px-6 py-4">
                        {{ $footer }}
                    </div>
                @endif
            </div>
        </div>
    </template>
</div>
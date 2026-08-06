@props([
    'state' => '',
    'label' => '',
    'icon' => '',
])

<div>
    <button @click="{{ $state }} = !{{ $state }}"
            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-200 text-ink-muted hover:bg-black/5 hover:opacity-100">
        <i class="{{ $icon }} text-base opacity-70" aria-hidden="true"></i>
        <span class="flex-1 text-left">{{ $label }}</span>
        <i class="fa-solid fa-chevron-down text-sm transition-transform duration-200" :class="{ 'rotate-180': {{ $state }} }" aria-hidden="true"></i>
    </button>
    <div x-show="{{ $state }}"
         x-collapse
         class="mt-1 ml-2 pl-3 border-l border-border space-y-0.5">
        {{ $slot }}
    </div>
</div>

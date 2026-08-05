@props([
    'label' => null,
    'name' => null,
    'required' => false,
    'help' => null,
])

<div {{ $attributes->only('class') }}>
    @if ($label || $slot)
        <label for="{{ $name }}" class="mb-1 block text-xs font-medium text-ink-muted">
            {{ $label ?? $slot }}
            @if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    {{ $control }}

    @if ($help)
        <p class="mt-1 text-xs text-ink-subtle">{{ $help }}</p>
    @endif

    @error($name)
        <p class="mt-1 text-xs font-medium text-danger">{{ $message }}</p>
    @enderror
</div>
@props([
    'url' => '#',
    'label' => '',
    'icon' => '',
    'iconSize' => 'text-sm opacity-70',
    'permission' => true,
    'swalError' => null,
    'active' => false,
])

@php($disabled = ! $permission)

<a href="{{ $url }}"
   aria-current="{{ $active ? 'page' : 'false' }}"
   class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-200 text-ink-muted hover:bg-black/5 {{ $disabled ? 'disabled' : '' }}"
   {!! $disabled && $swalError ? 'onclick="'.$swalError.'"' : '' !!}>
    <i class="{{ $icon }} {{ $iconSize }}" aria-hidden="true"></i>
    <span>{{ $label }}</span>
</a>
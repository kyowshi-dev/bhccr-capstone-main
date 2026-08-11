@php
    $tabs = [
        ['label' => 'Prenatal', 'url' => route('maternal.prenatal.index'), 'active' => request()->routeIs('maternal.prenatal*')],
        ['label' => 'Postpartum', 'url' => route('maternal.postnatal.index'), 'active' => request()->routeIs('maternal.postnatal*')],
        ['label' => 'Family Planning', 'url' => route('maternal.family-planning.index'), 'active' => request()->routeIs('maternal.family-planning*')],
    ];
@endphp

<nav class="flex flex-wrap items-center gap-1 border-b -mb-px" style="border-color: var(--border);" aria-label="Maternal care">
    @foreach ($tabs as $tab)
        <a href="{{ $tab['url'] }}"
           aria-current="{{ $tab['active'] ? 'page' : 'false' }}"
           class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-t-lg border-b-2 text-sm font-medium transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-accent-blue -mb-px {{ $tab['active'] ? 'font-semibold' : 'hover:text-ink hover:bg-black/[0.03] text-ink-muted' }}"
           style="{{ $tab['active'] ? 'color: var(--primary); border-color: var(--primary); background: var(--teal-soft);' : 'border-color: transparent;' }}">
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
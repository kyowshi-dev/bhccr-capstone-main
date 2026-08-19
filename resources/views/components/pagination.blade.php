@props([
    'paginator',
    'options' => [],
])

@php
    $options = array_values(array_unique(array_merge($options ?: pageSizeOptions(), [$paginator->perPage()])));
    sort($options);
    $first = $paginator->firstItem();
    $last = $paginator->lastItem();
    $total = $paginator->total();
@endphp

@if ($total > 0)
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-2 text-xs order-2 sm:order-1" style="color: var(--ink-muted);">
            <label for="rows-per-page" class="whitespace-nowrap">Rows per page</label>
            <select
                id="rows-per-page"
                x-data="{}"
                x-on:change="const u = new URL(window.location.href); u.searchParams.set('per_page', $event.target.value); u.searchParams.delete('page'); window.location.href = u.toString();"
                class="rounded-lg border px-2 py-1.5 text-xs focus:outline-none focus:ring-2 transition"
                style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);"
            >
                @foreach ($options as $option)
                    <option value="{{ $option }}" @selected($option === $paginator->perPage())>{{ $option }}</option>
                @endforeach
            </select>
            <span class="whitespace-nowrap">
                Showing <span class="font-medium" style="color: var(--ink);">{{ $first ?? 0 }}</span>–<span class="font-medium" style="color: var(--ink);">{{ $last ?? 0 }}</span> of <span class="font-medium" style="color: var(--ink);">{{ $total }}</span> records
            </span>
        </div>
        @if ($paginator->hasPages())
            <div class="order-1 sm:order-2 flex justify-center sm:justify-end min-h-[2.25rem] items-center">
                {{ $paginator->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
@endif
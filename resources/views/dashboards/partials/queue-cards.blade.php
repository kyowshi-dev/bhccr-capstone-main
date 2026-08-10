@forelse ($items as $item)
    @include('dashboards.partials.maternal-queue-card', ['item' => $item])
@empty
    <div class="rounded-xl border border-dashed p-6 sm:p-8 text-center" style="background: var(--bg-surface); border-color: var(--border);">
        <i class="fa-solid fa-circle-check text-2xl mb-2" style="color: var(--primary);" aria-hidden="true"></i>
        <p class="font-semibold text-sm" style="color: var(--ink);">All caught up</p>
        <p class="text-xs mt-1" style="color: var(--ink-muted);">
            @if ($tab === 'all')
                No patients in any service queues right now.
            @elseif ($tab === 'watchlist')
                No high-risk patients flagged.
            @else
                No patients in this service queue right now.
            @endif
        </p>
    </div>
@endforelse

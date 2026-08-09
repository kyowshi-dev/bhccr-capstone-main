@php
$rowId = (int) ($patientId ?? 0);
$name = (string) ($patientName ?? '');
$ident = (string) ($identifier ?? '');
$detailText = (string) ($detail ?? '');
$badgeType = (string) ($badge ?? '');
$badgeText = (string) ($badgeLabel ?? '');
$rowAction = (string) ($action ?? 'log_prenatal_visit');
$hasActive = (bool) ($hasActive ?? false);
@endphp
<div class="flex items-center justify-between gap-3 px-4 py-3 border-b last:border-b-0 transition hover:bg-black/[0.02]"
     style="border-color: var(--border);">
    <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2">
            <a href="{{ route('maternal.prenatal.patient', $rowId) }}" class="font-semibold text-sm hover:underline truncate focus:outline-none focus:ring-2 rounded-sm"
               style="color: var(--ink); --tw-ring-color: var(--accent-blue);">{{ $name }}</a>
            <span class="text-xs" style="color: var(--ink-muted);">{{ $ident }}</span>
        </div>
        <p class="text-xs mt-0.5" style="color: var(--ink-muted);">{{ $detailText }}</p>
    </div>
    @if ($badgeType === 'overdue')
        <span class="inline-flex items-center gap-1 shrink-0 px-2 py-1 rounded-full text-xs font-semibold"
              style="background: var(--danger-soft); color: var(--danger);">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> {{ $badgeText }}
        </span>
    @elseif ($badgeType === 'due')
        <span class="inline-flex items-center gap-1 shrink-0 px-2 py-1 rounded-full text-xs font-semibold"
              style="background: var(--accent-blue-soft); color: var(--accent-blue);">
            <i class="fa-solid fa-calendar-check" aria-hidden="true"></i> {{ $badgeText }}
        </span>
    @elseif ($badgeType === 'high-risk')
        <span class="inline-flex items-center gap-1 shrink-0 px-2 py-1 rounded-full text-xs font-semibold"
              style="background: var(--amber-soft); color: var(--amber);">
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> {{ $badgeText }}
        </span>
    @endif
    <button type="button"
            @click="$dispatch('maternal-quick-open', { patientId: {{ $rowId }}, patientName: '{{ addslashes($name) }}', hasActive: {{ $hasActive ? 'true' : 'false' }}, action: '{{ addslashes($rowAction) }}' })"
            class="inline-flex items-center gap-1.5 shrink-0 px-3 py-1.5 rounded-lg text-xs font-semibold transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
            style="background: var(--primary); color: #fff; --tw-ring-color: var(--accent-blue);">
        <i class="fa-solid fa-plus" aria-hidden="true"></i> Log visit
    </button>
</div>

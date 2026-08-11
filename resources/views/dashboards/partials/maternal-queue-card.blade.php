@props(['item'])

@php
    $patientRoute = route('patients.show', $item->patient_id);
    $programActions = [
        'prenatal' => ['action' => 'log_prenatal_visit', 'label' => 'Log Prenatal Visit', 'icon' => 'fa-person-pregnant'],
        'postnatal' => ['action' => 'log_postpartum', 'label' => 'Log Postnatal Visit', 'icon' => 'fa-baby'],
        'fp' => ['action' => 'log_fp_visit', 'label' => 'Log FP Visit', 'icon' => 'fa-pills'],
    ];
    $currentAction = $programActions[$item->program_type] ?? null;
    $secondaryActions = collect($item->context_badges ?? [])
        ->filter(fn($b) => ($b['program_type'] ?? '') !== $item->program_type)
        ->map(fn($b) => $programActions[$b['program_type']] ?? null)
        ->filter();
@endphp

<div class="rounded-xl border px-4 py-3 flex items-start justify-between gap-3 transition hover:bg-black/[0.02]"
     style="border-color: var(--border); background: var(--bg-surface-elevated); box-shadow: var(--shadow-sm);">

    <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ $patientRoute }}"
               class="font-semibold text-sm hover:underline truncate"
               style="color: var(--ink);">
                {{ $item->patient_name }}
            </a>
            <span class="text-xs shrink-0" style="color: var(--ink-muted);">
                ({{ $item->patient_code }})
            </span>

            @foreach($item->context_badges ?? [] as $badge)
                <span class="inline-flex items-center gap-1 shrink-0 px-2 py-0.5 rounded-full text-[10px] font-semibold border"
                      style="background: var(--{{ $badge['state'] === 'overdue' ? 'danger' : 'accent-blue' }}-soft); color: var(--{{ $badge['state'] === 'overdue' ? 'danger' : 'accent-blue' }}); border-color: var(--{{ $badge['state'] === 'overdue' ? 'danger' : 'accent-blue' }})/30;">
                    {{ $badge['label'] }}
                </span>
            @endforeach

            @if($item->risk_level === 'high')
                <span class="inline-flex items-center gap-1 shrink-0 px-2 py-0.5 rounded-full text-[10px] font-semibold border"
                      style="background: var(--amber-soft); color: var(--amber); border-color: var(--amber)/30;">
                    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> High-risk
                </span>
            @endif

            @if($item->escalated)
                <span class="inline-flex items-center gap-1 shrink-0 px-2 py-0.5 rounded-full text-[10px] font-semibold border"
                      style="background: var(--amber-soft); color: var(--amber); border-color: var(--amber)/30;">
                    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Escalated
                </span>
            @endif
        </div>

        <p class="text-xs mt-1" style="color: var(--ink-muted);">
            @include("dashboards.partials.cards.meta-{$item->program_type}", ['item' => $item])
        </p>
    </div>

    <div class="shrink-0 flex items-center gap-1.5">
        @if($currentAction)
            @if(auth()->user()->hasPermission('maternal'))
                <button type="button"
                        onclick="window.dispatchEvent(new CustomEvent('maternal-quick-open', { detail: { id: {{ $item->patient_id }}, patientId: {{ $item->patient_id }}, action: '{{ $currentAction['action'] }}', hasActive: {{ $item->is_checked_in_today ? 'true' : 'false' }}, patientName: '{{ addslashes($item->patient_name) }}' } }))"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold text-white transition hover:opacity-90"
                        style="background: var(--primary);">
                    <i class="fa-solid fa-{{ $currentAction['icon'] }}" aria-hidden="true"></i>
                    {{ $currentAction['label'] }}
                </button>
            @endif
        @endif

        @if($item->is_grouped && $secondaryActions->isNotEmpty())
            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg border text-xs transition hover:bg-black/[0.03]"
                        style="border-color: var(--border); color: var(--ink-muted);">
                    <i class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i>
                </button>
                <div x-show="open" x-transition @click.outside="open = false"
                     class="absolute right-0 top-full mt-1 rounded-xl border bg-surface shadow-lg z-10 min-w-[180px] py-1"
                     style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-md);">
                    @foreach($secondaryActions as $action)
                        <button type="button"
                                onclick="window.dispatchEvent(new CustomEvent('maternal-quick-open', { detail: { id: {{ $item->patient_id }}, patientId: {{ $item->patient_id }}, action: '{{ $action['action'] }}', hasActive: false, patientName: '{{ addslashes($item->patient_name) }}' } }))"
                                class="w-full text-left px-4 py-2 text-xs font-medium transition hover:bg-black/[0.03] flex items-center gap-2"
                                style="color: var(--ink);">
                            <i class="fa-solid fa-{{ $action['icon'] }}" aria-hidden="true"></i>
                            {{ $action['label'] }}
                        </button>
                    @endforeach
                    <a href="{{ $patientRoute }}"
                       class="block px-4 py-2 text-xs font-medium transition hover:bg-black/[0.03] flex items-center gap-2"
                       style="color: var(--ink-muted);">
                        <i class="fa-solid fa-user" aria-hidden="true"></i>
                        View Profile
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>

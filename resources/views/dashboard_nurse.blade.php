@extends('layouts.app')

@section('title', 'Nurse Dashboard')

@section('content')
@php
    $todayLabel = now()->format('F d, Y');
    $weekdayLabel = now()->format('l');
@endphp

<div class="space-y-5 lg:space-y-6">
    <div class="animate-in opacity-0 delay-1 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Nurse Dashboard</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">E-check ang vitals sa pasyente ug ipasa sila ngadto sa linya sa doktor.</p>
        </div>

        <div class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs sm:text-sm"
             style="background: var(--bg-surface); border-color: var(--border); color: var(--ink-muted); box-shadow: var(--shadow-sm);">
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg"
                  style="background: var(--teal-soft); color: var(--primary);">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                    <path d="M16 2v4"></path>
                    <path d="M8 2v4"></path>
                    <path d="M3 10h18"></path>
                </svg>
            </span>
            <div class="leading-tight">
                <div class="font-semibold" style="color: var(--ink);">{{ $todayLabel }}</div>
                <div class="text-xs" style="color: var(--ink-muted);">{{ $weekdayLabel }}</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 lg:gap-4">
        <div class="kpi-card animate-in opacity-0 delay-2 flex items-center gap-3 p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--primary);">
            <span class="kpi-card__icon" style="background: var(--teal-soft); color: var(--primary);">
                <i class="fa-solid fa-user-check" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="kpi-card__label truncate">Patients assisted</p>
                <p class="kpi-card__value">{{ $consultationsToday }}</p>
                <a href="{{ route('consultations.index') }}" class="text-[10px] font-bold truncate block mt-0.5" style="color: var(--primary);">View visits</a>
            </div>
        </div>

        <div class="kpi-card animate-in opacity-0 delay-3 flex items-center gap-3 p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
             style="background: {{ $pendingValidationCount > 0 ? 'var(--accent-blue-soft)' : 'var(--bg-surface)' }}; border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--accent-blue);">
            <span class="kpi-card__icon" style="background: {{ $pendingValidationCount > 0 ? 'var(--accent-blue)' : 'var(--teal-soft)' }}; color: {{ $pendingValidationCount > 0 ? '#fff' : 'var(--accent-blue)' }};">
                <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="kpi-card__label truncate" style="color: {{ $pendingValidationCount > 0 ? 'var(--accent-blue)' : 'var(--ink-muted)' }};">Pending review</p>
                <p class="kpi-card__value">{{ $pendingValidationCount }}</p>
                @if ($pendingValidationCount > 0)
                    <a href="{{ route('consultations.index', ['status' => \App\Enums\ConsultationStatus::NurseReview->value]) }}" class="text-[10px] font-bold truncate block mt-0.5" style="color: var(--accent-blue);">Open validation queue</a>
                @else
                    <p class="text-[10px] truncate mt-0.5" style="color: var(--ink-muted);">All caught up</p>
                @endif
            </div>
        </div>

        <div class="kpi-card animate-in opacity-0 delay-4 flex items-center gap-3 p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--primary);">
            <span class="kpi-card__icon" style="background: var(--teal-soft); color: var(--primary);">
                <i class="fa-solid fa-people-group" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="kpi-card__label truncate">Total active queue</p>
                <p class="kpi-card__value">{{ $intakePipelineCount }}</p>
                <a href="{{ route('consultations.index') }}" class="text-[10px] font-bold truncate block mt-0.5" style="color: var(--primary);">View pipeline</a>
            </div>
        </div>
    </div>

    <div class="animate-in opacity-0 delay-5 rounded-xl border p-4 lg:p-5"
         style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--accent);">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
            <div>
                <h2 class="font-display font-semibold text-lg" style="color: var(--ink);">Vitals Review Queue</h2>
                <p class="text-xs mt-1" style="color: var(--ink-muted);">
                    @if ($pendingValidationCount > 0)
                        {{ $pendingValidationCount }} intake{{ $pendingValidationCount !== 1 ? 's' : '' }} awaiting acknowledgment before doctor review.
                    @else
                        First come, first served. No pending intakes in the validation queue.
                    @endif
                </p>
            </div>
            <a href="{{ route('consultations.index', ['queue' => 1, 'status' => \App\Enums\ConsultationStatus::NurseReview->value]) }}" class="text-xs font-semibold hover:underline" style="color: var(--primary);">View All</a>
        </div>
        <ul class="space-y-2">
            @forelse ($validationQueue as $item)
                <li class="rounded-xl border px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                    style="border-color: var(--border); background: var(--bg-surface-elevated);">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold" style="color: var(--ink);">{{ fullName($item->last_name, $item->first_name) }}</p>
                        <p class="text-xs mt-0.5" style="color: var(--ink-muted);">{{ \Carbon\Carbon::parse($item->created_at)->diffForHumans() }}@if ($item->complaint_text) · {{ Str::limit($item->complaint_text, 60) }}@endif</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('consultations.show', $item->id) }}" class="px-3 py-2 rounded-lg text-xs font-semibold border transition hover:bg-black/[0.02]" style="border-color: var(--border); color: var(--primary);">View Details</a>
                        <form action="{{ route('consultations.acknowledge-intake', $item->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3 py-2 rounded-lg text-xs font-semibold text-white transition hover:opacity-90" style="background: var(--accent);">
                                Send to Doctor
                            </button>
                        </form>
                        <form action="{{ route('consultations.cancel', $item->id) }}" method="POST" onsubmit="return confirmCancelIntake(this);">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-2 rounded-lg text-xs font-semibold border transition hover:bg-black/[0.02]" style="border-color: var(--border); color: var(--ink-muted);">
                                Cancel
                            </button>
                        </form>
                    </div>
                </li>
            @empty
                <li class="rounded-xl border px-4 py-10 text-center" style="border-color: var(--border); background: var(--bg-surface-elevated);">
                    <div class="mx-auto w-12 h-12 rounded-full flex items-center justify-center"
                         style="background: var(--teal-soft); color: var(--primary);">
                        <i class="fa-solid fa-clipboard-check text-lg" aria-hidden="true"></i>
                    </div>
                    <p class="mt-3 text-sm font-semibold" style="color: var(--ink);">The validation queue is clear</p>
                    <p class="text-xs mt-1" style="color: var(--ink-muted);">New intakes will appear here as soon as patients are triaged.</p>
                </li>
            @endforelse
        </ul>
    </div>

    @if ($showResultsReady ?? false)
        @include('dashboard.partials.results-ready', [
            'panelTitle' => 'Recent completed — print handouts',
            'panelSubtitle' => 'Today’s finalized consultations. Print Rx and diagnosis summaries for patient pickup.',
            'showFilters' => true,
            'filterAction' => route('dashboard'),
        ])
    @endif
</div>
@endsection

@push('scripts')
<script>
    function confirmCancelIntake(form) {
        Swal.fire({
            title: 'Cancel this intake?',
            text: 'The patient will be removed from the validation queue. This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'var(--danger)',
            cancelButtonColor: 'var(--ink-muted)',
            confirmButtonText: 'Yes, cancel intake',
            cancelButtonText: 'No, keep it',
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
        return false;
    }
</script>
@endpush

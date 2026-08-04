@extends('layouts.app')

@section('content')
<div class="space-y-5 lg:space-y-6">
    <div class="animate-in opacity-0 delay-1 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Nurse Dashboard</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">E-check ang vitals sa pasyente ug ipasa sila ngadto sa linya sa doktor.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="animate-in opacity-0 delay-2 p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--primary);">
            <p class="text-[11px] font-semibold uppercase tracking-wider mb-2" style="color: var(--ink-muted);">Patients Assisted</p>
            <p class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">{{ $consultationsToday }}</p>
            <p class="text-xs mt-2" style="color: var(--ink-muted);">All visits logged today</p>
        </div>

        <div class="animate-in opacity-0 delay-3 p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--accent);">
            <p class="text-[11px] font-semibold uppercase tracking-wider mb-2" style="color: var(--ink-muted);">Pending Review</p>
            <p class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">{{ $pendingValidationCount }}</p>
            <p class="text-xs mt-2" style="color: var(--ink-muted);">Intakes pending nurse acknowledgment</p>
        </div>

        <div class="animate-in opacity-0 delay-4 p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--primary);">
            <p class="text-[11px] font-semibold uppercase tracking-wider mb-2" style="color: var(--ink-muted);">Total Active Queue</p>
            <p class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">{{ $intakePipelineCount }}</p>
            <p class="text-xs mt-2" style="color: var(--ink-muted);">Triage and validation stages combined</p>
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
                    <div>
                        <p class="text-sm font-semibold" style="color: var(--ink);">{{ $item->last_name }}, {{ ucwords ($item->first_name) }}</p>
                        <p class="text-xs mt-0.5" style="color: var(--ink-muted);">{{ \Carbon\Carbon::parse($item->created_at)->diffForHumans() }}@if ($item->complaint_text) · {{ Str::limit($item->complaint_text, 60) }}@endif</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('consultations.show', $item->id) }}" class="px-3 py-2 rounded-lg text-xs font-semibold border transition hover:bg-black/[0.02]" style="border-color: var(--border); color: var(--primary);">View Details</a>
                        <form action="{{ route('consultations.acknowledge-intake', $item->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3 py-2 rounded-lg text-xs font-semibold text-white transition hover:opacity-90" style="background: var(--accent);">
                                Send to Doctor
                            </button>
                        </form>
                        <form action="{{ route('consultations.cancel', $item->id) }}" method="POST" onsubmit="return confirm('Cancel this intake?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-2 rounded-lg text-xs font-semibold border transition hover:bg-black/[0.02]" style="border-color: var(--border); color: var(--ink-muted);">
                                Cancel
                            </button>
                        </form>
                    </div>
                </li>
            @empty
                <li class="rounded-xl border px-4 py-8 text-center" style="border-color: var(--border); background: var(--bg-surface-elevated); color: var(--ink-muted);">
                    The validation queue is clear.
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

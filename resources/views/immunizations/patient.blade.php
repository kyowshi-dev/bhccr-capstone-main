@extends('layouts.app')

@section('title', 'Immunization — ' . $patient->last_name . ', ' . $patient->first_name)

@section('content')
<div class="space-y-5 lg:space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <a href="{{ route('patients.show', $patient->id) }}" class="text-sm font-medium hover:underline mb-1 inline-block" style="color: var(--primary);">← Back to patient</a>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Immunization — {{ $patient->last_name }}, {{ $patient->first_name }}</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">{{ $patient->age }} y/o · {{ $patient->sex }} · DOB {{ \Carbon\Carbon::parse($patient->date_of_birth)->format('M j, Y') }}</p>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-xl border px-4 py-3" style="background: var(--teal-soft); border-color: var(--primary); color: var(--primary);">
            {{ session('success') }}
        </div>
    @endif

    <div>
        <h2 class="font-display font-semibold text-lg mb-1" style="color: var(--ink);">Immunization schedule</h2>
        <p class="text-sm mb-3" style="color: var(--ink-muted);">Tap Administered to log today’s dose. Dose number advances automatically from prior records.</p>
        <div class="rounded-xl border overflow-hidden" style="background: var(--bg-surface-elevated); border-color: var(--border);">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead style="background: var(--teal-soft);">
                        <tr>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium" style="color: var(--ink-muted);">Vaccine</th>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium hidden md:table-cell" style="color: var(--ink-muted);">Schedule</th>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium" style="color: var(--ink-muted);">Status</th>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-right text-xs font-medium" style="color: var(--ink-muted);">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border)]">
                        @forelse ($schedule as $item)
                            @php
                                $isDone = $item->doses_given > 0;
                                $isOverdue = $item->next_due_date && \Carbon\Carbon::parse($item->next_due_date)->isBefore(\Carbon\Carbon::today());
                            @endphp
                            <tr class="transition-colors hover:bg-black/[0.02]">
                                <td class="px-3 lg:px-4 py-3" style="color: var(--ink);">
                                    <div class="font-medium">{{ $item->vaccine->vaccine_name }}</div>
                                    @if ($item->vaccine->vaccine_code)
                                        <div class="text-xs mt-0.5" style="color: var(--ink-muted);">{{ $item->vaccine->vaccine_code }}</div>
                                    @endif
                                </td>
                                <td class="px-3 lg:px-4 py-3 hidden md:table-cell text-xs" style="color: var(--ink-muted);">
                                    {{ $item->vaccine->description ?? 'Per DOH schedule' }}
                                </td>
                                <td class="px-3 lg:px-4 py-3">
                                    @if ($isDone)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold" style="background: var(--teal-soft); color: var(--primary);">
                                            Dose {{ $item->latest_dose_number }} · {{ \Carbon\Carbon::parse($item->latest_date)->format('M d, Y') }}
                                        </span>
                                        @if ($isOverdue)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-red-100 text-red-800 mt-1">Due</span>
                                        @endif
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold" style="background: rgba(0,0,0,0.06); color: var(--ink-muted);">Not yet given</span>
                                    @endif
                                </td>
                                <td class="px-3 lg:px-4 py-3 text-right">
                                    <form action="{{ route('immunizations.administer', $patient->id) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Record {{ $item->vaccine->vaccine_name }} as administered today?');">
                                        @csrf
                                        <input type="hidden" name="vaccine_id" value="{{ $item->vaccine->id }}">
                                        <button type="submit"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition hover:opacity-90"
                                                style="background: var(--primary);">
                                            <i class="fa-solid fa-syringe" aria-hidden="true"></i>
                                            Administered
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-sm" style="color: var(--ink-muted);">No vaccines in schedule for this age group.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div>
        <h2 class="font-display font-semibold text-lg mb-3" style="color: var(--ink);">History</h2>
        <div class="rounded-xl border overflow-hidden" style="background: var(--bg-surface-elevated); border-color: var(--border);">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead style="background: var(--teal-soft);">
                        <tr>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);">Date</th>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);">Vaccine</th>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);">Dose</th>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap hidden sm:table-cell" style="color: var(--ink-muted);">Given by</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border)]">
                        @forelse ($records as $r)
                            <tr class="transition-colors hover:bg-black/[0.02]">
                                <td class="px-3 lg:px-4 py-2 lg:py-3 whitespace-nowrap" style="color: var(--ink);">{{ \Carbon\Carbon::parse($r->date_given)->format('M d, Y') }}</td>
                                <td class="px-3 lg:px-4 py-2 lg:py-3" style="color: var(--ink);">{{ $r->vaccine_name }}</td>
                                <td class="px-3 lg:px-4 py-2 lg:py-3" style="color: var(--ink);">{{ $r->dose_number }}</td>
                                <td class="px-3 lg:px-4 py-2 lg:py-3 hidden sm:table-cell" style="color: var(--ink-muted);">{{ $r->administered_by_name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 lg:px-4 py-6 text-center text-sm" style="color: var(--ink-muted);">No doses logged yet. Use Administered on the schedule above.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

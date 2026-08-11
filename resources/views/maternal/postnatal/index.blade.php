@extends('layouts.app')

@section('title', 'Postnatal')

@section('content')
<div class="space-y-5 lg:space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Postnatal</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Postpartum mothers and remaining 24h / 7d / 14d / 28d visits.</p>
        </div>
    </div>

    <x-maternal-nav-tabs />

    <form method="GET" action="{{ route('maternal.postnatal.index') }}" class="flex flex-wrap items-center gap-2">
        <div>
            <label for="filter_zone" class="sr-only">Filter by purok</label>
            <select id="filter_zone" name="zone_id" @change="this.form.submit()"
                    class="rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                    style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                <option value="">All puroks</option>
                @foreach ($zones as $zone)
                    <option value="{{ $zone->id }}" @selected((int) $zoneId === (int) $zone->id)>{{ $zone->zone_number }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="search" class="sr-only">Search patient</label>
            <input id="search" type="search" name="search" value="{{ $search }}" placeholder="Search patient name…"
                   class="rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                   style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
        </div>
        <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-semibold transition hover:bg-black/[0.03]"
                style="border-color: var(--border); color: var(--ink-muted);">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Filter
        </button>
        @if ($zoneId !== null || $search !== null)
            <a href="{{ route('maternal.postnatal.index') }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold transition hover:bg-black/[0.03]" style="color: var(--ink-muted);">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear
            </a>
        @endif
    </form>

    @if ($records->isEmpty())
        <div class="rounded-xl border border-dashed p-10 text-center" style="background: var(--bg-surface); border-color: var(--border);">
            <i class="fa-solid fa-child-reaching text-3xl mb-3" style="color: var(--ink-subtle);" aria-hidden="true"></i>
            <p class="font-semibold" style="color: var(--ink);">No postnatal records</p>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Record a delivery to start the 24h / 7d / 14d / 28d postpartum schedule.</p>
            <a href="{{ route('patients.index') }}" class="inline-flex items-center justify-center mt-4 px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition hover:shadow-md"
               style="background: var(--primary);">
                <i class="fa-solid fa-user-injured mr-1.5" aria-hidden="true"></i> Browse patients
            </a>
        </div>
    @else
        <div class="rounded-xl border overflow-hidden" style="background: var(--bg-surface); border-color: var(--border);">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="border-b" style="background: var(--teal-soft);">
                        <tr class="text-xs uppercase tracking-wide" style="color: var(--ink-muted);">
                            <th class="px-4 py-3 font-semibold whitespace-nowrap">Mother</th>
                            <th class="px-4 py-3 font-semibold whitespace-nowrap hidden md:table-cell">Zone</th>
                            <th class="px-4 py-3 font-semibold whitespace-nowrap">Delivered</th>
                            <th class="px-4 py-3 font-semibold whitespace-nowrap">Outcome</th>
                            <th class="px-4 py-3 font-semibold whitespace-nowrap">Next postpartum visit</th>
                            <th class="px-4 py-3 font-semibold whitespace-nowrap text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="border-color: var(--border);">
                        @foreach ($records as $record)
                            @php
                                $slots = [
                                    ['col' => 'postpartum_24h_date', 'window' => 1, 'label' => '24h'],
                                    ['col' => 'postpartum_7d_date', 'window' => 7, 'label' => '7d'],
                                    ['col' => 'postpartum_14d_date', 'window' => 14, 'label' => '14d'],
                                    ['col' => 'postpartum_28d_date', 'window' => 28, 'label' => '28d'],
                                ];
                                $next = null;
                                foreach ($slots as $slot) {
                                    if ($record->{$slot['col']} === null) {
                                        $next = $slot;
                                        break;
                                    }
                                }
                                $nextDate = $next !== null ? \Carbon\Carbon::parse($record->delivery_date)->addDays($next['window']) : null;
                                $overdue = $nextDate !== null && $nextDate->lt(\Carbon\Carbon::today());
                            @endphp
                            <tr class="hover:bg-black/[0.03] transition">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <a href="{{ route('maternal.postnatal.patient', $record->patient_id) }}" class="font-medium hover:underline" style="color: var(--ink);">
                                        {{ fullName($record->patient->last_name, $record->patient->first_name, $record->patient->middle_name, $record->patient->suffix) }}
                                    </a>
                                    @if (! empty($record->danger_signs_mother))
                                        <span class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold" style="background: var(--amber-soft); color: var(--amber);">
                                            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> High risk
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap hidden md:table-cell" style="color: var(--ink-muted);">Zone {{ $record->patient->household?->zone?->zone_number ?? $record->patient->household?->zone_id ?? '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap font-medium" style="color: var(--ink);">{{ $record->delivery_date->format('M d, Y') }}</td>
                                <td class="px-4 py-3 whitespace-nowrap" style="color: var(--ink-muted);">{{ \App\Models\PostnatalRecord::OUTCOMES[$record->pregnancy_outcome] ?? $record->pregnancy_outcome }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($next === null)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--teal-soft); color: var(--primary);">
                                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Complete
                                        </span>
                                    @elseif ($overdue)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--danger-soft); color: var(--danger);">
                                            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> {{ $next['label'] }} overdue ({{ $nextDate->format('M d') }})
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--accent-blue-soft); color: var(--accent-blue);">
                                            <i class="fa-regular fa-calendar" aria-hidden="true"></i> {{ $next['label'] }} · {{ $nextDate->format('M d') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <a href="{{ route('maternal.postnatal.patient', $record->patient_id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                                       style="background: var(--teal-soft); color: var(--primary);">
                                        Open <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

@extends('layouts.app')

@section('title', 'Family Planning')

@section('content')
<div class="space-y-5 lg:space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Family planning</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Registered FP clients, method, and follow-up schedule.</p>
        </div>
    </div>

    <x-maternal-nav-tabs />

    <form method="GET" action="{{ route('maternal.family-planning.index') }}" class="flex flex-wrap items-center gap-2">
        <div class="inline-flex items-center rounded-lg border p-1" style="border-color: var(--border);">
            @foreach ([['value' => 'active', 'label' => 'Active'], ['value' => 'all', 'label' => 'Show All']] as $option)
                <button type="submit" name="status" value="{{ $option['value'] }}"
                        class="rounded-md px-3 py-1.5 text-xs font-semibold transition focus:outline-none focus:ring-2"
                        style="{{ ($status ?? 'active') === $option['value']
                            ? 'background: var(--primary); color: #fff; --tw-ring-color: var(--accent-blue);'
                            : 'color: var(--ink-muted); --tw-ring-color: var(--accent-blue);' }}">
                    {{ $option['label'] }}
                </button>
            @endforeach
        </div>
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
            <a href="{{ route('maternal.family-planning.index') }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold transition hover:bg-black/[0.03]" style="color: var(--ink-muted);">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear
            </a>
        @endif
    </form>

    @if ($clients->isEmpty())
        <div class="rounded-xl border border-dashed p-10 text-center" style="background: var(--bg-surface); border-color: var(--border);">
            <i class="fa-solid fa-hand-holding-heart text-3xl mb-3" style="color: var(--ink-subtle);" aria-hidden="true"></i>
            <p class="font-semibold" style="color: var(--ink);">No family planning clients yet</p>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Open a patient profile and register a client under Maternal Care.</p>
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
                            <th class="px-4 py-3 font-semibold whitespace-nowrap">Patient</th>
                            <th class="px-4 py-3 font-semibold whitespace-nowrap hidden md:table-cell">Zone</th>
                            <th class="px-4 py-3 font-semibold whitespace-nowrap">Type</th>
                            <th class="px-4 py-3 font-semibold whitespace-nowrap">Method</th>
                            <th class="px-4 py-3 font-semibold whitespace-nowrap">Next follow-up</th>
                            <th class="px-4 py-3 font-semibold whitespace-nowrap text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="border-color: var(--border);">
                        @foreach ($clients as $client)
                            @php
                                $overdue = $client->is_active && $client->schedule_next_visit !== null && \Carbon\Carbon::parse($client->schedule_next_visit)->lt(\Carbon\Carbon::today());
                                $dueSoon = $client->is_active && $client->schedule_next_visit !== null && ! $overdue && \Carbon\Carbon::parse($client->schedule_next_visit)->lte(\Carbon\Carbon::today()->addDays(7));
                            @endphp
                            <tr class="hover:bg-black/[0.03] transition">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <a href="{{ route('maternal.family-planning.patient', $client->patient_id) }}" class="font-medium hover:underline" style="color: var(--ink);">
                                        {{ fullName($client->patient->last_name, $client->patient->first_name, $client->patient->middle_name, $client->patient->suffix) }}
                                    </a>
                                    @if (! $client->is_active)
                                        <span class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold" style="background: var(--amber-soft); color: var(--amber);">
                                            <i class="fa-solid fa-circle-user" aria-hidden="true"></i> Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap hidden md:table-cell" style="color: var(--ink-muted);">Zone {{ $client->patient->household?->zone?->zone_number ?? $client->patient->household?->zone_id ?? '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap" style="color: var(--ink-muted);">{{ \App\Models\FamilyPlanningClient::TYPES[$client->type_of_client] ?? $client->type_of_client }}</td>
                                <td class="px-4 py-3 whitespace-nowrap font-medium" style="color: var(--ink);">{{ $client->method }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if (! $client->is_active || $client->schedule_next_visit === null)
                                        <span style="color: var(--ink-subtle);">-</span>
                                    @elseif ($overdue)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--danger-soft); color: var(--danger);">
                                            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> Overdue {{ \Carbon\Carbon::parse($client->schedule_next_visit)->format('M d') }}
                                        </span>
                                    @elseif ($dueSoon)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--accent-blue-soft); color: var(--accent-blue);">
                                            <i class="fa-regular fa-calendar" aria-hidden="true"></i> {{ \Carbon\Carbon::parse($client->schedule_next_visit)->format('M d') }}
                                        </span>
                                    @else
                                        <span style="color: var(--ink-muted);">{{ \Carbon\Carbon::parse($client->schedule_next_visit)->format('M d, Y') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <a href="{{ route('maternal.family-planning.patient', $client->patient_id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition"
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

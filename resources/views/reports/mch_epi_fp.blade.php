@extends('layouts.app')

@section('title', 'Maternal, EPI & Family Planning Report')

@section('content')
        <div class="space-y-4 lg:space-y-6">
            <div>
                <h1 class="flex items-center justify-between text-xl lg:text-2xl font-extrabold text-ink mt-1 lg:mt-2">
                    <span>Maternal, EPI & Family Planning Report</span>
                    <a href="{{ route('reports.index') }}" class="ml-4 text-xs lg:text-sm font-medium hover:opacity-90" style="color: var(--primary);">Back to Reports</a>
                </h1>
            </div>

            <form method="GET" action="{{ route('reports.mch-epi-fp') }}" id="mchEpiFpFilterForm" class="space-y-4">
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4 items-end">
                    <div>
                        <label for="from" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">From</label>
                        <input type="date" id="from" name="from" value="{{ $filters['from'] }}" class="w-full rounded-lg border py-2 text-xs lg:text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                    </div>

                    <div>
                        <label for="to" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">To</label>
                        <input type="date" id="to" name="to" value="{{ $filters['to'] }}" class="w-full rounded-lg border py-2 text-xs lg:text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                    </div>

                    <div>
                        <label for="zone" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Zone</label>
                        <select id="zone" name="zone" class="w-full rounded-lg border py-2 text-xs lg:text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                            <option value="">All Zones</option>
                            @foreach($zones as $z)
                                <option value="{{ $z->id }}" @selected((string)($filters['zone'] ?? '') === (string)$z->id)>{{ $z->zone_number }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="program" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Program</label>
                        <select id="program" name="program" class="w-full rounded-lg border py-2 text-xs lg:text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                            <option value="all" @selected($filters['program'] === 'all')>All Programs</option>
                            @foreach(\App\Services\MchEpiFpReportService::PROGRAMS as $key => $label)
                                <option value="{{ $key }}" @selected($filters['program'] === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-end gap-2">
                    <div class="flex-1">
                        <label for="search" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Search patient</label>
                        <input type="text" id="search" name="search" value="{{ $filters['search'] }}" placeholder="Last name or first name" class="w-full rounded-lg border py-2 text-xs lg:text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);" autocomplete="off">
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-2 rounded-lg text-white text-xs lg:text-sm font-medium transition-colors hover:opacity-90" style="background: var(--primary);">Apply</button>
                        <button type="button" x-on:click="downloadPdf()" class="px-4 py-2 rounded-lg text-white text-xs lg:text-sm font-medium transition-colors hover:opacity-90" style="background: var(--primary);">
                            Download PDF
                        </button>
                    </div>
                </div>
            </form>
        </div>

    @php
        $maternal = $report['summaries']['maternal'] ?? null;
        $epi = $report['summaries']['epi'] ?? null;
        $fp = $report['summaries']['fp'] ?? null;
    @endphp

    @if ($maternal !== null || $epi !== null || $fp !== null)
        <div class="grid gap-4 md:grid-cols-3 mt-6">
            @if ($maternal !== null)
                <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
                    <h2 class="text-sm font-semibold mb-3" style="color: var(--ink);">Maternal Care</h2>
                    <dl class="space-y-2 text-xs lg:text-sm">
                        <div class="flex justify-between gap-3">
                            <dt style="color: var(--ink-muted);">New prenatal clients</dt>
                            <dd class="font-semibold" style="color: var(--ink);">{{ number_format($maternal['newPrenatalClients']) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt style="color: var(--ink-muted);">Prenatal visits</dt>
                            <dd class="font-semibold" style="color: var(--ink);">{{ number_format($maternal['prenatalVisits']) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt style="color: var(--ink-muted);">Women with 4+ visits</dt>
                            <dd class="font-semibold" style="color: var(--ink);">{{ number_format($maternal['prenatalFourPlus']) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt style="color: var(--ink-muted);">Deliveries</dt>
                            <dd class="font-semibold" style="color: var(--ink);">{{ number_format($maternal['totalDeliveries']) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt style="color: var(--ink-muted);">Postnatal visits</dt>
                            <dd class="font-semibold" style="color: var(--ink);">{{ number_format($maternal['postpartum24h'] + $maternal['postpartum7d'] + $maternal['postpartum14d'] + $maternal['postpartum28d']) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 text-xs" style="color: var(--ink-muted);">
                            <dt>24h / 7d / 14d / 28d</dt>
                            <dd>{{ number_format($maternal['postpartum24h']) }} / {{ number_format($maternal['postpartum7d']) }} / {{ number_format($maternal['postpartum14d']) }} / {{ number_format($maternal['postpartum28d']) }}</dd>
                        </div>
                    </dl>
                </div>
            @endif

            @if ($epi !== null)
                <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
                    <h2 class="text-sm font-semibold mb-3" style="color: var(--ink);">EPI Immunization</h2>
                    <dl class="space-y-2 text-xs lg:text-sm">
                        <div class="flex justify-between gap-3">
                            <dt style="color: var(--ink-muted);">Child doses</dt>
                            <dd class="font-semibold" style="color: var(--ink);">{{ number_format($epi['childDoses']) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt style="color: var(--ink-muted);">Adult doses</dt>
                            <dd class="font-semibold" style="color: var(--ink);">{{ number_format($epi['adultDoses']) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt style="color: var(--ink-muted);">Total doses given</dt>
                            <dd class="font-semibold" style="color: var(--ink);">{{ number_format($epi['totalDoses']) }}</dd>
                        </div>
                    </dl>
                </div>
            @endif

            @if ($fp !== null)
                <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
                    <h2 class="text-sm font-semibold mb-3" style="color: var(--ink);">Family Planning</h2>
                    <dl class="space-y-2 text-xs lg:text-sm">
                        <div class="flex justify-between gap-3">
                            <dt style="color: var(--ink-muted);">New acceptors</dt>
                            <dd class="font-semibold" style="color: var(--ink);">{{ number_format($fp['totalNew']) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt style="color: var(--ink-muted);">Continuing users</dt>
                            <dd class="font-semibold" style="color: var(--ink);">{{ number_format($fp['totalContinuing']) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt style="color: var(--ink-muted);">Drop outs</dt>
                            <dd class="font-semibold" style="color: var(--ink);">{{ number_format($fp['totalDropOuts']) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt style="color: var(--ink-muted);">Others</dt>
                            <dd class="font-semibold" style="color: var(--ink);">{{ number_format($fp['totalOthers']) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt style="color: var(--ink-muted);">Visits conducted</dt>
                            <dd class="font-semibold" style="color: var(--ink);">{{ number_format($fp['totalVisits']) }}</dd>
                        </div>
                    </dl>
                </div>
            @endif
        </div>
    @endif

    @php
        $registerPrograms = [
            \App\Services\MchEpiFpReportService::PROGRAM_MATERNAL,
            \App\Services\MchEpiFpReportService::PROGRAM_FP,
            \App\Services\MchEpiFpReportService::PROGRAM_EPI,
        ];
        $rowsByProgram = $report['rows']->groupBy('program');
    @endphp

    <div class="bg-surface rounded-xl mt-7 lg:rounded-2xl border border-border overflow-hidden print:shadow-none">
        <div class="p-3 lg:p-6 border-b border-border bg-teal-soft">
            <p class="font-semibold text-xs lg:text-sm text-ink">Integrated Health Information System - Sta. Ana</p>
            <p class="text-xs lg:text-sm text-ink-muted">In Compliance with Department of Health - Field Health Service Information System (FHSIS)</p>
            <p class="text-xs lg:text-sm text-ink-muted mt-1">Report Period: {{ $report['reportDate'] }}</p>
        </div>

        @foreach ($registerPrograms as $registerProgram)
            @if ($report['summaries'][$registerProgram] === null)
                @continue
            @endif

            @php
                $programRows = $rowsByProgram->get($registerProgram, collect());
                $programTotal = (int) ($report['programCounts'][$registerProgram] ?? 0);
                $programLabel = \App\Services\MchEpiFpReportService::PROGRAMS[$registerProgram];
            @endphp

            <div class="px-3 lg:px-4 py-2 lg:py-3 border-b border-border bg-teal-soft">
                <h2 class="text-xs lg:text-sm font-semibold text-ink">
                    {{ $programLabel }}
                    <span class="font-normal" style="color: var(--ink-muted);">({{ number_format($programTotal) }} records)</span>
                </h2>
            </div>

            @if ($programRows->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-xs lg:text-sm">
                        <thead class="bg-teal-soft border-b border-border">
                            <tr>
                                <th class="px-2 lg:px-4 py-2 lg:py-3 font-semibold text-ink w-28 whitespace-nowrap">Date</th>
                                <th class="px-2 lg:px-4 py-2 lg:py-3 font-semibold text-ink">Service</th>
                                <th class="px-2 lg:px-4 py-2 lg:py-3 font-semibold text-ink">Patient</th>
                                <th class="px-2 lg:px-4 py-2 lg:py-3 font-semibold text-ink whitespace-nowrap">Zone</th>
                                <th class="px-2 lg:px-4 py-2 lg:py-3 font-semibold text-ink whitespace-nowrap">Health Worker</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($programRows as $row)
                                <tr class="hover:bg-black/5">
                                    <td class="px-2 lg:px-4 py-2 lg:py-3 text-ink whitespace-nowrap">{{ \Carbon\Carbon::parse($row->date)->format('m/d/Y') }}</td>
                                    <td class="px-2 lg:px-4 py-2 lg:py-3 text-ink">{{ $row->service }}</td>
                                    <td class="px-2 lg:px-4 py-2 lg:py-3 text-ink">
                                        <div class="font-medium">{{ $row->patient_name }}</div>
                                        <div class="text-xs mt-0.5" style="color: var(--ink-muted);">{{ $row->patient_code }}</div>
                                    </td>
                                    <td class="px-2 lg:px-4 py-2 lg:py-3 text-ink whitespace-nowrap">{{ $row->zone }}</td>
                                    <td class="px-2 lg:px-4 py-2 lg:py-3 text-ink whitespace-nowrap">{{ $row->worker_name ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-4 py-4 lg:py-5 text-center text-sm" style="color: var(--ink-muted);">
                    {{ $programTotal === 0 ? "No {$programLabel} records for this period." : "No {$programLabel} records on this page." }}
                </div>
            @endif
        @endforeach

        @if ($report['totalRows'] > 0)
            <div class="border-t px-3 lg:px-4 py-2 lg:py-3" style="border-color: var(--border);">
                <x-pagination :paginator="$report['rows']" />
            </div>
        @endif
    </div>

<script>
(function () {
    const form = document.getElementById('mchEpiFpFilterForm');
    if (!form) return;

    const submitForm = function () {
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
            return;
        }
        form.submit();
    };

    form.addEventListener('change', function (e) {
        const target = e.target;
        if (!target) return;

        if (['from', 'to', 'zone', 'program'].includes(target.id)) {
            submitForm();
        }
    });
})();

function downloadPdf() {
    const url = new URL('{{ route("reports.mch-epi-fp.download") }}', window.location.origin);
    const fromEl = document.getElementById('from');
    const toEl = document.getElementById('to');
    const zoneEl = document.getElementById('zone');
    const programEl = document.getElementById('program');
    const searchEl = document.getElementById('search');

    if (fromEl) url.searchParams.set('from', fromEl.value);
    if (toEl) url.searchParams.set('to', toEl.value);
    if (zoneEl && zoneEl.value) url.searchParams.set('zone', zoneEl.value);
    if (programEl && programEl.value !== 'all') url.searchParams.set('program', programEl.value);
    if (searchEl && searchEl.value.trim()) url.searchParams.set('search', searchEl.value.trim());

    window.open(url.toString(), '_blank');
}
</script>
@endsection

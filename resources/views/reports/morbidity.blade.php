@extends('layouts.app')

@section('title', 'Morbidity Report')

@section('content')
        <div class="space-y-4 lg:space-y-6">
            <div>
                <h1 class="flex items-center justify-between text-xl lg:text-2xl font-extrabold text-gray-800 mt-1 lg:mt-2">
                    <span>Month Morbidity Disease Report</span>
                    <a href="{{ route('reports.index') }}" class="ml-4 text-xs lg:text-sm font-medium hover:opacity-90" style="color: var(--primary);">Back to Reports</a>
                </h1>
            </div>

            <form id="morbidityFilterForm" method="GET" action="{{ route('reports.morbidity') }}" class="space-y-4">
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4 items-end">
                    <div>
                        <label for="month" class="block text-xs font-medium" style="color: var(--ink-muted);">Month</label>
                        <select id="month" name="month" class="w-full rounded-lg border py-2 text-xs lg:text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                            @foreach (range(1, 12) as $m)
                                <option value="{{ $m }}" @selected($month === $m)>{{ \Carbon\Carbon::createFromDate(null, $m, 1)->format('M') }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="year" class="block text-xs font-medium" style="color: var(--ink-muted);">Year</label>
                        <input type="number" id="year" name="year" value="{{ $year }}" min="2020" max="{{ date('Y') + 1 }}" class="w-full rounded-lg border py-2 text-xs lg:text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                    </div>

                    <div>
                        <label for="sex" class="block text-xs font-medium" style="color: var(--ink-muted);">Sex</label>
                        <select id="sex" name="sex" class="w-full rounded-lg border py-2 text-xs lg:text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                            <option value="All" @selected(($sex ?? 'All') === 'All')>All Sex</option>
                            <option value="M" @selected(($sex ?? '') === 'M')>Male</option>
                            <option value="F" @selected(($sex ?? '') === 'F')>Female</option>
                        </select>
                    </div>

                    <div>
                        <label for="zone" class="block text-xs font-medium" style="color: var(--ink-muted);">Zone</label>
                        <select id="zone" name="zone" class="w-full rounded-lg border py-2 text-xs lg:text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                            <option value="">All Zones</option>
                            @if(!empty($zones))
                                @foreach($zones as $z)
                                    <option value="{{ $z->id }}" @selected((string)($selectedZone ?? '') === (string)$z->id)>{{ $z->zone_number }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-end gap-2">
                    <div class="flex-1">
                        <label for="age_group" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Age group</label>
                        <select id="age_group" name="age_group" class="w-full rounded-lg border py-2 text-xs lg:text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                            <option value="all" @selected(($age_group ?? 'all') === 'all')>All ages</option>
                            <optgroup label="Infants">
                                <option value="infant_0_6d" @selected(($age_group ?? '') === 'infant_0_6d')>0–6 days</option>
                                <option value="infant_7_28d" @selected(($age_group ?? '') === 'infant_7_28d')>7–28 days</option>
                                <option value="infant_29_11m" @selected(($age_group ?? '') === 'infant_29_11m')>29 days – 11 months</option>
                            </optgroup>
                            <optgroup label="Children">
                                <option value="child_1_4" @selected(($age_group ?? '') === 'child_1_4')>1–4 years</option>
                                <option value="child_5_9" @selected(($age_group ?? '') === 'child_5_9')>5–9 years</option>
                                <option value="child_10_14" @selected(($age_group ?? '') === 'child_10_14')>10–14 years</option>
                            </optgroup>
                            <optgroup label="Adults & Elderly">
                                @foreach(range(15, 65, 5) as $start)
                                    @php $end = $start + 4; @endphp
                                    <option value="{{ $start }}_{{ $end }}" @selected(($age_group ?? '') === "{$start}_{$end}")>{{ $start }}–{{ $end }} years</option>
                                @endforeach
                                <option value="70_plus" @selected(($age_group ?? '') === '70_plus')>≥ 70 years</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="sm:w-40">
                        <button
                            type="button"
                            x-on:click="downloadPdf()"
                            class="w-full px-3 lg:px-4 py-2 rounded-lg text-white text-xs lg:text-sm font-medium transition-colors hover:opacity-90"
                            style="background: var(--primary);">
                            Download PDF
                        </button>
                    </div>
                </div>
            </form>
        </div>

    <div class="bg-white rounded-xl mt-7 lg:rounded-2xl border border-gray-200 overflow-hidden print:shadow-none">
        <div class="p-3 lg:p-6 border-b border-gray-200 bg-gray-50/80">
            <p class="font-semibold text-xs lg:text-sm text-gray-700">Integrated Health Information System - Sta. Ana</p>
            <p class="text-xs lg:text-sm text-gray-600">In Compliance with Department of Health - Field Health Service Information System (FHSIS)</p>
            <p class="text-xs lg:text-sm text-gray-500 mt-1">Report Period: {{ $reportDate }}</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-xs lg:text-sm">
                <thead class="bg-gray-100 border-b border-gray-200">
                    <tr>
                        <th class="px-2 lg:px-4 py-2 lg:py-3 font-semibold text-gray-700 w-12 lg:w-16 whitespace-nowrap">Rank</th>
                        <th class="px-2 lg:px-4 py-2 lg:py-3 font-semibold text-gray-700 w-20 lg:w-24 whitespace-nowrap">ICD Code</th>
                        <th class="px-2 lg:px-4 py-2 lg:py-3 font-semibold text-gray-700">Diagnosis / Cause</th>
                        <th class="px-2 lg:px-4 py-2 lg:py-3 font-semibold text-gray-700 w-20 lg:w-24 text-right whitespace-nowrap">Cases</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($rows as $rank => $row)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-2 lg:px-4 py-2 lg:py-3 font-medium text-gray-800">{{ $rank + 1 }}</td>
                            <td class="px-2 lg:px-4 py-2 lg:py-3 text-gray-700">{{ $row->diagnosis_code }}</td>
                            <td class="px-2 lg:px-4 py-2 lg:py-3 text-gray-800">{{ $row->diagnosis_name }}</td>
                            <td class="px-2 lg:px-4 py-2 lg:py-3 text-right font-semibold text-gray-800">{{ number_format($row->case_count) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 lg:py-8 text-center text-gray-500 text-sm">No morbidity data for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($rows->isNotEmpty())
            <div class="px-3 lg:px-4 py-2 lg:py-3 bg-gray-50 border-t border-gray-200 text-xs lg:text-sm font-semibold text-gray-700">
                Total cases: {{ number_format($totalCases) }}
            </div>
        @endif
</div>

<script>
(function () {
    const form = document.getElementById('morbidityFilterForm');
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

        if (['month', 'year', 'sex', 'zone', 'age_group'].includes(target.id)) {
            submitForm();
        }
    });
})();

function downloadPdf() {
    const url = new URL('{{ route("reports.morbidity.download") }}', window.location.origin);
    const monthEl = document.getElementById('month');
    const yearEl = document.getElementById('year');
    const sexEl = document.getElementById('sex');
    const zoneEl = document.getElementById('zone');
    const ageEl = document.getElementById('age_group');

    if (monthEl) url.searchParams.set('month', monthEl.value);
    if (yearEl) url.searchParams.set('year', yearEl.value);
    if (sexEl) url.searchParams.set('sex', sexEl.value);
    if (zoneEl && zoneEl.value) url.searchParams.set('zone', zoneEl.value);
    if (ageEl) url.searchParams.set('age_group', ageEl.value);

    window.open(url.toString(), '_blank');
}
</script>
@endsection

@extends('layouts.app')

@section('title', 'Maternal Care Report')

@section('content')
        <div class="space-y-4 lg:space-y-6">
            <div>
                <h1 class="flex items-center justify-between text-xl lg:text-2xl font-extrabold text-ink mt-1 lg:mt-2">
                    <span>Maternal Care Report</span>
                    <a href="{{ route('reports.index') }}" class="ml-4 text-xs lg:text-sm font-medium hover:opacity-90" style="color: var(--primary);">Back to Reports</a>
                </h1>
            </div>

            <form method="GET" action="{{ route('reports.maternal-care') }}" id="maternalFilterForm" class="space-y-4">
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4 items-end">
                    <div>
                        <label for="month" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Month</label>
                        <select id="month" name="month" class="w-full rounded-lg border py-2 text-xs lg:text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                            @foreach (range(1, 12) as $m)
                                <option value="{{ $m }}" @selected($month === $m)>{{ \Carbon\Carbon::createFromDate(null, $m, 1)->format('M') }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="year" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Year</label>
                        <input type="number" id="year" name="year" value="{{ $year }}" min="2020" max="{{ date('Y') + 1 }}" class="w-full rounded-lg border py-2 text-xs lg:text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                    </div>

                    <div>
                        <label for="zone" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Zone</label>
                        <select id="zone" name="zone" class="w-full rounded-lg border py-2 text-xs lg:text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                            <option value="">All Zones</option>
                            @foreach($zones as $z)
                                <option value="{{ $z->id }}" @selected((string)($selectedZone ?? '') === (string)$z->id)>{{ $z->zone_number }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:w-40">
                        <button type="button" x-on:click="downloadPdf()" class="w-full px-3 lg:px-4 py-2 rounded-lg text-white text-xs lg:text-sm font-medium transition-colors hover:opacity-90" style="background: var(--primary);">
                            Download PDF
                        </button>
                    </div>
                </div>
            </form>
        </div>

    <div class="bg-surface rounded-xl mt-7 lg:rounded-2xl border border-border overflow-hidden print:shadow-none">
        <div class="p-3 lg:p-6 border-b border-border bg-teal-soft">
            <p class="font-semibold text-xs lg:text-sm text-ink">Integrated Health Information System - Sta. Ana</p>
            <p class="text-xs lg:text-sm text-ink-muted">In Compliance with Department of Health - Field Health Service Information System (FHSIS)</p>
            <p class="text-xs lg:text-sm text-ink-muted mt-1">Report Period: {{ $report['reportDate'] }}</p>
        </div>

        <div class="p-4 lg:p-6 space-y-6">
            <div>
                <h2 class="text-sm lg:text-base font-semibold text-ink mb-3">Prenatal Care</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-xs lg:text-sm">
                        <thead class="bg-teal-soft border-b border-border">
                            <tr>
                                <th class="px-2 lg:px-4 py-2 lg:py-3 font-semibold text-ink">Indicator</th>
                                <th class="px-2 lg:px-4 py-2 lg:py-3 font-semibold text-ink w-24 text-right whitespace-nowrap">Count</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr class="hover:bg-black/5">
                                <td class="px-2 lg:px-4 py-2 lg:py-3 text-ink">New prenatal clients registered</td>
                                <td class="px-2 lg:px-4 py-2 lg:py-3 text-right font-semibold text-ink">{{ number_format($report['newPrenatalClients']) }}</td>
                            </tr>
                            <tr class="hover:bg-black/5">
                                <td class="px-2 lg:px-4 py-2 lg:py-3 text-ink">Prenatal visits conducted</td>
                                <td class="px-2 lg:px-4 py-2 lg:py-3 text-right font-semibold text-ink">{{ number_format($report['prenatalVisits']) }}</td>
                            </tr>
                            <tr class="hover:bg-black/5">
                                <td class="px-2 lg:px-4 py-2 lg:py-3 text-ink">Pregnant women with 4+ prenatal visits</td>
                                <td class="px-2 lg:px-4 py-2 lg:py-3 text-right font-semibold text-ink">{{ number_format($report['prenatalFourPlus']) }}</td>
                            </tr>
                            <tr class="hover:bg-black/5">
                                <td class="px-2 lg:px-4 py-2 lg:py-3 text-ink">TT/Td doses given</td>
                                <td class="px-2 lg:px-4 py-2 lg:py-3 text-right font-semibold text-ink">{{ number_format($report['ttDoses']) }}</td>
                            </tr>
                            <tr class="hover:bg-black/5">
                                <td class="px-2 lg:px-4 py-2 lg:py-3 text-ink">With iron supplementation</td>
                                <td class="px-2 lg:px-4 py-2 lg:py-3 text-right font-semibold text-ink">{{ number_format($report['ironSupplemented']) }}</td>
                            </tr>
                            <tr class="hover:bg-black/5">
                                <td class="px-2 lg:px-4 py-2 lg:py-3 text-ink">Syphilis-positive screened</td>
                                <td class="px-2 lg:px-4 py-2 lg:py-3 text-right font-semibold text-ink">{{ number_format($report['syphilisPositive']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <h2 class="text-sm lg:text-base font-semibold text-ink mb-3">Deliveries ({{ number_format($report['totalDeliveries']) }})</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="overflow-x-auto">
                        <p class="text-xs font-medium mb-2" style="color: var(--ink-muted);">By place of delivery</p>
                        <table class="min-w-full text-left text-xs lg:text-sm">
                            <thead class="bg-teal-soft border-b border-border">
                                <tr>
                                    <th class="px-2 lg:px-4 py-2 font-semibold text-ink">Place</th>
                                    <th class="px-2 lg:px-4 py-2 font-semibold text-ink w-20 text-right">Count</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @forelse ($report['deliveriesByPlace'] as $row)
                                    <tr class="hover:bg-black/5">
                                        <td class="px-2 lg:px-4 py-2 text-ink">{{ \App\Services\MaternalCareReportService::placeLabel($row->key) }}</td>
                                        <td class="px-2 lg:px-4 py-2 text-right font-semibold text-ink">{{ number_format($row->total) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="px-4 py-4 text-center text-ink-muted text-xs">No deliveries.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="overflow-x-auto">
                        <p class="text-xs font-medium mb-2" style="color: var(--ink-muted);">By attendant at birth</p>
                        <table class="min-w-full text-left text-xs lg:text-sm">
                            <thead class="bg-teal-soft border-b border-border">
                                <tr>
                                    <th class="px-2 lg:px-4 py-2 font-semibold text-ink">Attendant</th>
                                    <th class="px-2 lg:px-4 py-2 font-semibold text-ink w-20 text-right">Count</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @forelse ($report['deliveriesByAttendant'] as $row)
                                    <tr class="hover:bg-black/5">
                                        <td class="px-2 lg:px-4 py-2 text-ink">{{ \App\Services\MaternalCareReportService::attendantLabel($row->key) }}</td>
                                        <td class="px-2 lg:px-4 py-2 text-right font-semibold text-ink">{{ number_format($row->total) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="px-4 py-4 text-center text-ink-muted text-xs">No deliveries.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="overflow-x-auto">
                        <p class="text-xs font-medium mb-2" style="color: var(--ink-muted);">By outcome</p>
                        <table class="min-w-full text-left text-xs lg:text-sm">
                            <thead class="bg-teal-soft border-b border-border">
                                <tr>
                                    <th class="px-2 lg:px-4 py-2 font-semibold text-ink">Outcome</th>
                                    <th class="px-2 lg:px-4 py-2 font-semibold text-ink w-20 text-right">Count</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @forelse ($report['deliveriesByOutcome'] as $row)
                                    <tr class="hover:bg-black/5">
                                        <td class="px-2 lg:px-4 py-2 text-ink">{{ \App\Services\MaternalCareReportService::outcomeLabel($row->key) }}</td>
                                        <td class="px-2 lg:px-4 py-2 text-right font-semibold text-ink">{{ number_format($row->total) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="px-4 py-4 text-center text-ink-muted text-xs">No deliveries.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div>
                <h2 class="text-sm lg:text-base font-semibold text-ink mb-3">Postpartum / Postnatal Visits</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-xs lg:text-sm">
                        <thead class="bg-teal-soft border-b border-border">
                            <tr>
                                <th class="px-2 lg:px-4 py-2 lg:py-3 font-semibold text-ink">Within 24 hours</th>
                                <th class="px-2 lg:px-4 py-2 lg:py-3 font-semibold text-ink">Within 7 days</th>
                                <th class="px-2 lg:px-4 py-2 lg:py-3 font-semibold text-ink">Within 14 days</th>
                                <th class="px-2 lg:px-4 py-2 lg:py-3 font-semibold text-ink">Within 28 days</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr class="hover:bg-black/5">
                                <td class="px-2 lg:px-4 py-2 lg:py-3 text-right font-semibold text-ink">{{ number_format($report['postpartum24h']) }}</td>
                                <td class="px-2 lg:px-4 py-2 lg:py-3 text-right font-semibold text-ink">{{ number_format($report['postpartum7d']) }}</td>
                                <td class="px-2 lg:px-4 py-2 lg:py-3 text-right font-semibold text-ink">{{ number_format($report['postpartum14d']) }}</td>
                                <td class="px-2 lg:px-4 py-2 lg:py-3 text-right font-semibold text-ink">{{ number_format($report['postpartum28d']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<script>
(function () {
    const form = document.getElementById('maternalFilterForm');
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

        if (['month', 'year', 'zone'].includes(target.id)) {
            submitForm();
        }
    });
})();

function downloadPdf() {
    const url = new URL('{{ route("reports.maternal-care.download") }}', window.location.origin);
    const monthEl = document.getElementById('month');
    const yearEl = document.getElementById('year');
    const zoneEl = document.getElementById('zone');

    if (monthEl) url.searchParams.set('month', monthEl.value);
    if (yearEl) url.searchParams.set('year', yearEl.value);
    if (zoneEl && zoneEl.value) url.searchParams.set('zone', zoneEl.value);

    window.open(url.toString(), '_blank');
}
</script>
@endsection

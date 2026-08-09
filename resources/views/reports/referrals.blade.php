@extends('layouts.app')

@section('title', 'Referral Report')

@section('content')
        <div class="space-y-4 lg:space-y-6">
            <div>
                <h1 class="flex items-center justify-between text-xl lg:text-2xl font-extrabold text-ink mt-1 lg:mt-2">
                    <span>Referral Report</span>
                    <a href="{{ route('reports.index') }}" class="ml-4 text-xs lg:text-sm font-medium hover:opacity-90" style="color: var(--primary);">Back to Reports</a>
                </h1>
            </div>

            <form method="GET" action="{{ route('reports.referrals') }}" id="referralFilterForm" class="space-y-4">
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
                <h2 class="text-sm lg:text-base font-semibold text-ink mb-3">Outward Referrals ({{ number_format($report['totalOutward']) }})</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-xs lg:text-sm">
                        <thead class="bg-teal-soft border-b border-border">
                            <tr>
                                <th class="px-2 lg:px-4 py-2 lg:py-3 font-semibold text-ink">Destination Facility</th>
                                <th class="px-2 lg:px-4 py-2 lg:py-3 font-semibold text-ink w-20 text-right whitespace-nowrap">Pending</th>
                                <th class="px-2 lg:px-4 py-2 lg:py-3 font-semibold text-ink w-24 text-right whitespace-nowrap">Completed</th>
                                <th class="px-2 lg:px-4 py-2 lg:py-3 font-semibold text-ink w-20 text-right whitespace-nowrap">No-Show</th>
                                <th class="px-2 lg:px-4 py-2 lg:py-3 font-semibold text-ink w-20 text-right whitespace-nowrap">Cancelled</th>
                                <th class="px-2 lg:px-4 py-2 lg:py-3 font-semibold text-ink w-20 text-right whitespace-nowrap">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse ($report['outwardByDestination'] as $row)
                                <tr class="hover:bg-black/5">
                                    <td class="px-2 lg:px-4 py-2 lg:py-3 text-ink">{{ $row->destination }}</td>
                                    <td class="px-2 lg:px-4 py-2 lg:py-3 text-right text-ink">{{ number_format($row->pending) }}</td>
                                    <td class="px-2 lg:px-4 py-2 lg:py-3 text-right text-ink">{{ number_format($row->completed) }}</td>
                                    <td class="px-2 lg:px-4 py-2 lg:py-3 text-right text-ink">{{ number_format($row->no_shows) }}</td>
                                    <td class="px-2 lg:px-4 py-2 lg:py-3 text-right text-ink">{{ number_format($row->cancelled) }}</td>
                                    <td class="px-2 lg:px-4 py-2 lg:py-3 text-right font-semibold text-ink">{{ number_format($row->total) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 lg:py-8 text-center text-ink-muted text-sm">No outward referrals for this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h2 class="text-sm lg:text-base font-semibold text-ink mb-3">Outward by Status</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-xs lg:text-sm">
                            <thead class="bg-teal-soft border-b border-border">
                                <tr>
                                    <th class="px-2 lg:px-4 py-2 font-semibold text-ink">Status</th>
                                    <th class="px-2 lg:px-4 py-2 font-semibold text-ink w-24 text-right">Count</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @forelse ($report['outwardByStatus'] as $row)
                                    <tr class="hover:bg-black/5">
                                        <td class="px-2 lg:px-4 py-2 text-ink">{{ \App\Services\ReferralReportService::statusLabel($row->status) }}</td>
                                        <td class="px-2 lg:px-4 py-2 text-right font-semibold text-ink">{{ number_format($row->total) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="px-4 py-4 text-center text-ink-muted text-xs">No referrals.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <h2 class="text-sm lg:text-base font-semibold text-ink mb-3">Incoming Referrals ({{ number_format($report['totalInward']) }})</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-xs lg:text-sm">
                            <thead class="bg-teal-soft border-b border-border">
                                <tr>
                                    <th class="px-2 lg:px-4 py-2 font-semibold text-ink">Source Facility</th>
                                    <th class="px-2 lg:px-4 py-2 font-semibold text-ink w-24 text-right">Count</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @forelse ($report['inwardBySource'] as $row)
                                    <tr class="hover:bg-black/5">
                                        <td class="px-2 lg:px-4 py-2 text-ink">{{ $row->source }}</td>
                                        <td class="px-2 lg:px-4 py-2 text-right font-semibold text-ink">{{ number_format($row->total) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="px-4 py-4 text-center text-ink-muted text-xs">No incoming referrals.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
(function () {
    const form = document.getElementById('referralFilterForm');
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
    const url = new URL('{{ route("reports.referrals.download") }}', window.location.origin);
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
@extends('layouts.app')

@section('content')
<div class="space-y-4 lg:space-y-6">
    <a href="{{ route('reports.index') }}" class="text-xs lg:text-sm font-medium text-sky-600 hover:text-sky-800"> Back to Reports</a>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl lg:text-2xl font-extrabold text-gray-800 mt-1 lg:mt-2">FHSIS Morbidity Report</h1>
        </div>
        <form method="GET" action="{{ route('reports.morbidity') }}" class="flex flex-col gap-3">
            <div class="flex items-end gap-2">
                <select name="month" class="rounded-lg border border-gray-300 text-xs lg:text-sm py-1.5 lg:py-2">
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}" @selected($month === $m)>{{ \Carbon\Carbon::createFromDate(null, $m, 1)->format('M') }}</option>
                    @endforeach
                </select>
                <input type="number" name="year" value="{{ $year }}" min="2020" max="{{ date('Y') + 1 }}" class="w-16 lg:w-20 rounded-lg border border-gray-300 text-xs lg:text-sm py-1.5 lg:py-2">
                <button type="submit" class="px-2 lg:px-3 py-1.5 lg:py-2 rounded-lg bg-sky-600 text-white text-xs lg:text-sm font-medium">Go</button>
            </div>
            <button
                x-on:click="downloadPdf()"
                class="px-3 lg:px-4 py-1.5 lg:py-2 rounded-lg bg-green-600 text-white text-xs lg:text-sm font-medium hover:bg-green-700 transition-colors"
            >
                Download PDF
            </button>
            
        </form>
    </div>

    <div class="bg-white rounded-xl lg:rounded-2xl border border-gray-200 overflow-hidden print:shadow-none">
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
</div>

<script>
function downloadPdf() {
    const url = new URL('{{ route("reports.morbidity.download") }}', window.location.origin);
    url.searchParams.set('month', {{ $month }});
    url.searchParams.set('year', {{ $year }});
    window.open(url.toString(), '_blank');
}
</script>
@endsection

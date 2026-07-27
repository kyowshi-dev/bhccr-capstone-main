@extends('layouts.app')

@section('content')
<div class="space-y-5 lg:space-y-6">
    <div>
        <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Reports</h1>
        <p class="text-sm mt-1" style="color: var(--ink-muted);">View or Generate DOH Field Health Service Information System Official report formats for RHU.</p>
    </div>

    <form method="GET" action="{{ route('reports.index') }}" id="reportPeriodForm" class="rounded-xl border p-4 max-w-md" style="background: var(--bg-surface); border-color: var(--border);">
        <h2 class="text-xs lg:text-sm font-semibold mb-2 lg:mb-3" style="color: var(--ink);">Report period</h2>
        <div class="flex flex-wrap items-end gap-2 lg:gap-3">
            <div class="flex-1 min-w-[100px]">
                <label for="month" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Month</label>
                <select id="month" name="month" class="w-full rounded-lg border py-2 text-xs lg:text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}" @selected($month === $m)>{{ \Carbon\Carbon::createFromDate(null, $m, 1)->format('F') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[80px]">
                <label for="year" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Year</label>
                <input type="number" id="year" name="year" value="{{ $year }}" min="2020" max="{{ date('Y') + 1 }}" class="w-full rounded-lg border py-2 text-xs lg:text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
            </div>
            <button type="submit" class="px-4 py-2 rounded-xl text-white text-sm font-semibold whitespace-nowrap transition" style="background: var(--primary);">Apply</button>
        </div>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-5">
        <a href="{{ route('reports.morbidity', ['month' => $month, 'year' => $year]) }}"
           class="block p-5 lg:p-6 rounded-xl border transition-all duration-200 hover:shadow-md" style="background: var(--bg-surface); border-color: var(--border);">
            <h3 class="font-display font-semibold text-base mb-1" style="color: var(--ink);">Morbidity report</h3>
            <p class="text-sm" style="color: var(--ink-muted);">Leading causes of morbidity by diagnosis (ICD). FHSIS standard format.</p>
        </a>
        {{-- <a href="{{ route('reports.consultation-summary', ['month' => $month, 'year' => $year]) }}"
           class="block p-5 lg:p-6 rounded-xl border transition-all duration-200 hover:shadow-md" style="background: var(--bg-surface); border-color: var(--border);">
            <h3 class="font-display font-semibold text-base mb-1" style="color: var(--ink);">Program summary</h3>
            <p class="text-sm" style="color: var(--ink-muted);">Monthly consolidation of consultations by program (general, prenatal, postpartum, immunization, family planning).</p>
        </a> --}}
        </div>

    <script>
        (function () {
            const form = document.getElementById('reportPeriodForm');
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

                if (target.id === 'month' || target.id === 'year') {
                    submitForm();
                }
            });
        })();
    </script>
</div>
@endsection

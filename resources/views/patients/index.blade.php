@extends('layouts.app')

@section('content')
@php
    $patientFiltersActive = request()->filled('sort') || request()->filled('dir');
    $patientSortNextUrl = function (string $column) use ($patientSort, $patientDir) {
        if ($patientSort === $column) {
            $nextDir = $patientDir === 'asc' ? 'desc' : 'asc';
        } else {
            $nextDir = match ($column) {
                'name' => 'asc',
                'age', 'last_visit', 'created' => 'desc',
                default => 'desc',
            };
        }

        return route('patients.index', array_filter([
            'sort' => $column,
            'dir' => $nextDir,
        ]));
    };
@endphp
<div class="space-y-5 lg:space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Patient Records</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Search and manage patient information.</p>
        </div>
        <a href="{{ url('/patients/create') }}"
           class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition-all duration-200 hover:opacity-95 active:scale-[0.98] shrink-0"
           style="background: #0d4a3c; box-shadow: 0 2px 8px rgba(196, 92, 65, 0.25);">
            Enrol New Patient 
        </a>
    </div>

    <div class="rounded-xl" x-data="patientSearch()">
        <div class="relative">
            <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none" style="color: var(--ink-subtle);">
            </span>
            <input type="text" x-model="query" @input.debounce.300ms="search()"
                   placeholder="Search"
                   class="w-full pl-10 pr-4 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 transition"
                   style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);"
                   autocomplete="off">
        </div>
        <div x-show="results.length > 0" class="mt-3 rounded-lg border overflow-hidden" style="display: none; border-color: var(--border); background: var(--bg-surface-elevated); box-shadow: var(--shadow-md);">
            <ul>
                <template x-for="patient in results" :key="patient.id">
                    <li class="border-b last:border-0 transition-colors hover:bg-black/[0.03]">
                        <a :href="'/patients/' + patient.id" class="block px-4 py-2.5">
                            <div class="font-medium text-sm" style="color: var(--ink);" x-text="patient.text"></div>
                            <div class="text-xs mt-0.5" style="color: var(--ink-muted);" x-text="patient.subtext"></div>
                        </a>
                    </li>
                </template>
            </ul>
        </div>
        <div x-show="query.length > 1 && results.length === 0 && !loading" class="mt-3 rounded-lg border p-6 text-center" style="display: none; border-color: var(--border); background: var(--bg-surface);">
            <p class="text-sm font-medium mt-2" style="color: var(--ink);">No results found</p>
            <p class="text-xs mt-1" style="color: var(--ink-muted);">Try a different search term or patient ID</p>
        </div>
    </div>


    <div class="rounded-xl border overflow-hidden" style="background: var(--bg-surface-elevated); border-color: var(--border); box-shadow: var(--shadow-sm);">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-xs lg:text-sm">
                <thead>
                    <tr style="background: var(--teal-soft);">
                        <th class="px-4 py-3 font-semibold uppercase tracking-wider whitespace-nowrap" style="color: var(--ink-muted);">ID</th>
                        <th class="px-4 py-3 font-semibold uppercase tracking-wider whitespace-nowrap" style="color: var(--ink-muted);">
                            <a href="{{ $patientSortNextUrl('name') }}" class="inline-flex items-center gap-1.5 hover:underline focus:outline-none focus:ring-2 rounded" style="color: inherit; --tw-ring-color: var(--primary);">
                                Name
                                @if ($patientSort === 'name')
                                    <i class="fa-solid {{ $patientDir === 'asc' ? 'fa-chevron-up' : 'fa-chevron-down' }} text-[10px]" aria-hidden="true"></i>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-3 font-semibold uppercase tracking-wider whitespace-nowrap" style="color: var(--ink-muted);">
                            <a href="{{ $patientSortNextUrl('age') }}" class="inline-flex items-center gap-1.5 hover:underline focus:outline-none focus:ring-2 rounded" style="color: inherit; --tw-ring-color: var(--primary);">
                                Age
                                @if ($patientSort === 'age')
                                    <i class="fa-solid {{ $patientDir === 'asc' ? 'fa-chevron-up' : 'fa-chevron-down' }} text-[10px]" aria-hidden="true"></i>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-3 font-semibold uppercase tracking-wider whitespace-nowrap hidden sm:table-cell" style="color: var(--ink-muted);">Gender</th>
                        <th class="px-4 py-3 font-semibold uppercase tracking-wider whitespace-nowrap hidden md:table-cell" style="color: var(--ink-muted);">Phone</th>
                        <th class="px-4 py-3 font-semibold uppercase tracking-wider whitespace-nowrap hidden lg:table-cell" style="color: var(--ink-muted);">
                            <a href="{{ $patientSortNextUrl('last_visit') }}" class="inline-flex items-center gap-1.5 hover:underline focus:outline-none focus:ring-2 rounded" style="color: inherit; --tw-ring-color: var(--primary);">
                                Last visit
                                @if ($patientSort === 'last_visit')
                                    <i class="fa-solid {{ $patientDir === 'asc' ? 'fa-chevron-up' : 'fa-chevron-down' }} text-[10px]" aria-hidden="true"></i>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-3 font-semibold uppercase tracking-wider text-right whitespace-nowrap" style="color: var(--ink-muted);">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--border)]">
                    @forelse ($patients as $patient)
                        <tr class="transition-colors hover:bg-black/[0.02]">
                            <td class="px-4 py-2.5 font-medium whitespace-nowrap" style="color: var(--ink);">PT{{ str_pad($patient->id, 3, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-4 py-2.5" style="color: var(--ink);">
                                <div class="font-medium">{{ ucfirst($patient->last_name) }}, {{ ucwords($patient->first_name) }}</div>
                                <div class="text-xs mt-0.5 line-clamp-2 text-slate-500" style="color: var(--ink-muted);">
                                    {{ \Carbon\Carbon::parse($patient->date_of_birth)->format('M j, Y') }}
                                    @if (! empty($patient->residential_address))
                                        <span class="text-slate-400"> · </span>{{ \Illuminate\Support\Str::limit($patient->residential_address, 52) }}
                                    @endif
                                </div>
                                <div class="text-xs sm:hidden mt-0.5" style="color: var(--ink-muted);">{{ $patient->sex }}</div>
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap" style="color: var(--ink-muted);">{{ \Carbon\Carbon::parse($patient->date_of_birth)->age }}</td>
                            <td class="px-4 py-2.5 whitespace-nowrap hidden sm:table-cell" style="color: var(--ink-muted);">{{ $patient->sex }}</td>
                            <td class="px-4 py-2.5 whitespace-nowrap hidden md:table-cell" style="color: var(--ink-muted);">{{ $patient->contact_number ?? '—' }}</td>
                            <td class="px-4 py-2.5 whitespace-nowrap hidden lg:table-cell" style="color: var(--ink-muted);">
                                @if ($patient->last_visit) {{ \Carbon\Carbon::parse($patient->last_visit)->format('Y-m-d') }} @else — @endif
                            </td>
                            <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                <a href="{{ route('patients.show', $patient->id) }}" class="font-semibold text-sm transition-colors hover:underline" style="color: var(--primary);">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12">
                                @if ($patientFiltersActive)
                                    <div class="text-center">
                                        <div class="mb-3 flex justify-center"><i class="fa-solid fa-filter-circle-xmark text-3xl" style="color: var(--ink-subtle);"></i></div>
                                        <p class="text-sm font-medium" style="color: var(--ink);">No records found</p>
                                        <p class="text-xs mt-1 mb-3" style="color: var(--ink-muted);">No patients match the current sort. Try clearing filters to see all records.</p>
                                        <a href="{{ route('patients.index') }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold text-white transition hover:opacity-90" style="background: var(--primary);"><i class="fa-solid fa-arrow-rotate-left"></i> Clear all filters</a>
                                    </div>
                                @else
                                    <div class="text-center">
                                        <div class="mb-3 flex justify-center"><i class="fa-solid fa-clipboard-list text-3xl" style="color: var(--ink-subtle);"></i></div>
                                        <p class="text-sm font-medium" style="color: var(--ink);">No patient records yet</p>
                                        <p class="text-xs mt-1 mb-3" style="color: var(--ink-muted);">Start by registering a patient to track their health data</p>
                                        <a href="{{ url('/patients/create') }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold text-white transition hover:opacity-90" style="background: var(--primary);"><i class="fa-solid fa-plus"></i> Register first patient</a>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($patients->total() > 0)
            <div class="border-t px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" style="border-color: var(--border);">
                <p class="text-xs order-2 sm:order-1" style="color: var(--ink-muted);">
                    Showing <span class="font-medium" style="color: var(--ink);">{{ $patients->firstItem() }}</span>–<span class="font-medium" style="color: var(--ink);">{{ $patients->lastItem() }}</span> of <span class="font-medium" style="color: var(--ink);">{{ $patients->total() }}</span> records
                </p>
                <div class="order-1 sm:order-2 flex justify-center sm:justify-end min-h-[2.25rem] items-center">
                    {{ $patients->onEachSide(1)->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

<script>
    function patientSearch() {
        return {
            query: '',
            results: [],
            loading: false,
            async search() {
                if (this.query.length < 2) { this.results = []; return; }
                this.loading = true;
                try {
                    const response = await fetch(`/search/patients?query=${this.query}`);
                    this.results = await response.json();
                } catch (e) { console.error('Search failed:', e); }
                this.loading = false;
            },
        };
    }
</script>

@if(session('success') && session('new_patient_id'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const patientId = {{ session('new_patient_id') }};
        Swal.fire({
            title: 'Patient Registered Successfully!',
            text: 'What would you like to do next?',
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: 'Proceed to Consultation',
            cancelButtonText: 'Back to Home',
            confirmButtonColor: '#0d4a3c',
            cancelButtonColor: '#6b7280',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                openConsultationCreateModal(patientId);
            } else {
                // Stay on the current page (patients index)
            }
        });
    });
</script>
@endif

@endsection

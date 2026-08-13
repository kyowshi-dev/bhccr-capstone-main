@extends('layouts.app')

@section('title', 'Maternal & Family Planning Register')

@section('content')
@php
    $todayLabel = now()->format('F d, Y');
    $weekdayLabel = now()->format('l');
@endphp

<div class="space-y-5 lg:space-y-6">
    {{-- Page Header --}}
    <div class="animate-in opacity-0 delay-1 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Maternal &amp; Family Planning Register</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Digital Target Client List for Sta. Ana — search residents and open the program registers.</p>
        </div>
        <div class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs sm:text-sm"
             style="background: var(--bg-surface); border-color: var(--border); color: var(--ink-muted); box-shadow: var(--shadow-sm);">
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg"
                  style="background: var(--teal-soft); color: var(--primary);">
                <i class="fa-solid fa-calendar" aria-hidden="true"></i>
            </span>
            <div class="leading-tight">
                <div class="font-semibold" style="color: var(--ink);">{{ $todayLabel }}</div>
                <div class="text-xs" style="color: var(--ink-muted);">{{ $weekdayLabel }}</div>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 animate-in opacity-0 delay-2">
        <a href="{{ route('maternal.prenatal.index') }}"
           class="kpi-card flex items-center gap-3 p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
           style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--primary);">
            <span class="kpi-card__icon" style="background: var(--teal-soft); color: var(--primary);">
                <i class="fa-solid fa-person-pregnant" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="kpi-card__label truncate">Active Pregnancies</p>
                <p class="kpi-card__value">{{ $activePregnancies }}</p>
                <p class="text-[10px] font-bold truncate mt-0.5" style="color: var(--primary);">Open prenatal register</p>
            </div>
        </a>

        <a href="{{ route('maternal.postnatal.index') }}"
           class="kpi-card flex items-center gap-3 p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
           style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--accent);">
            <span class="kpi-card__icon" style="background: var(--accent-soft); color: var(--accent);">
                <i class="fa-solid fa-baby" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="kpi-card__label truncate">Postnatal Mothers</p>
                <p class="kpi-card__value">{{ $postnatalMothers }}</p>
                <p class="text-[10px] font-bold truncate mt-0.5" style="color: var(--accent);">Open postpartum register</p>
            </div>
        </a>

        <a href="{{ route('maternal.family-planning.index') }}"
           class="kpi-card flex items-center gap-3 p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
           style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--accent-blue);">
            <span class="kpi-card__icon" style="background: var(--accent-blue-soft); color: var(--accent-blue);">
                <i class="fa-solid fa-pills" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="kpi-card__label truncate">FP Clients</p>
                <p class="kpi-card__value">{{ $fpClients }}</p>
                <p class="text-[10px] font-bold truncate mt-0.5" style="color: var(--accent-blue);">Open FP register</p>
            </div>
        </a>

        <a href="{{ route('maternal.prenatal.index') }}"
           class="kpi-card flex items-center gap-3 p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md"
           style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid {{ $followUpsDue > 0 ? 'var(--amber)' : 'var(--primary)' }};">
            <span class="kpi-card__icon" style="background: {{ $followUpsDue > 0 ? 'var(--amber-soft)' : 'var(--teal-soft)' }}; color: {{ $followUpsDue > 0 ? 'var(--amber)' : 'var(--primary)' }};">
                <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="kpi-card__label truncate">Follow-ups Due This Week</p>
                <p class="kpi-card__value" style="{{ $followUpsDue > 0 ? 'color: var(--amber);' : '' }}">{{ $followUpsDue }}</p>
                <p class="text-[10px] font-bold truncate mt-0.5" style="color: var(--ink-muted);">Overdue or due in 7 days</p>
            </div>
        </a>
    </div>

    {{-- Quick Search --}}
    <div class="animate-in opacity-0 delay-3 rounded-xl border p-4" x-data="residentSearch()"
         style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
        <div class="flex flex-col lg:flex-row lg:items-center gap-3">
            <div class="relative flex-1 min-w-0">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none" style="color: var(--ink-subtle);">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                </span>
                <input type="search" x-model="query" @input.debounce.300ms="search()"
                       placeholder="Search resident by name…"
                       class="w-full pl-10 pr-4 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 transition"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);"
                       autocomplete="off">
            </div>
            @if (auth()->user()?->hasPermission('patients'))
                <a href="{{ route('patients.create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold text-white transition hover:opacity-90 shrink-0" style="background: var(--primary);">
                    <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                    Register New Resident
                </a>
            @endif
        </div>
        <div x-show="results.length > 0" x-transition class="mt-3 rounded-lg border overflow-hidden" style="display: none; border-color: var(--border); background: var(--bg-surface-elevated); box-shadow: var(--shadow-md);">
            <ul>
                <template x-for="patient in results" :key="patient.id">
                    <li class="border-b last:border-0 transition-colors hover:bg-black/[0.03]">
                        <a :href="`/patients/${patient.id}`" class="block w-full text-left px-4 py-2.5">
                            <div class="font-medium text-sm" style="color: var(--ink);" x-text="patient.text"></div>
                            <div class="text-xs mt-0.5" style="color: var(--ink-muted);" x-text="patient.subtext"></div>
                        </a>
                    </li>
                </template>
            </ul>
        </div>
        <div x-show="query.length > 1 && results.length === 0 && !loading" x-transition class="mt-3 rounded-lg border p-6 text-center" style="display: none; border-color: var(--border); background: var(--bg-surface);">
            <div class="flex justify-center mb-2"><i class="fa-solid fa-user-magnifying-glass text-3xl" style="color: var(--ink-subtle);" aria-hidden="true"></i></div>
            <p class="text-sm font-medium" style="color: var(--ink);">No resident found</p>
            <p class="text-xs mt-1 mb-3" style="color: var(--ink-muted);">Try a different name, or register the resident first.</p>
            @if (auth()->user()?->hasPermission('patients'))
                <a href="{{ route('patients.create') }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold text-white transition hover:opacity-90" style="background: var(--primary);">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Register new resident
                </a>
            @endif
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="animate-in opacity-0 delay-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
        <a href="{{ route('maternal.prenatal.index') }}"
           class="flex items-center gap-3 rounded-xl border p-4 transition hover:bg-black/[0.02] hover:shadow-md"
           style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg" style="background: var(--teal-soft); color: var(--primary);">
                <i class="fa-solid fa-person-pregnant" aria-hidden="true"></i>
            </span>
            <div class="min-w-0">
                <p class="text-sm font-semibold" style="color: var(--ink);">Open Prenatal Register</p>
                <p class="text-xs mt-0.5 truncate" style="color: var(--ink-muted);">Active pregnancies, EDC, next visit</p>
            </div>
        </a>
        <a href="{{ route('maternal.family-planning.index') }}"
           class="flex items-center gap-3 rounded-xl border p-4 transition hover:bg-black/[0.02] hover:shadow-md"
           style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg" style="background: var(--accent-blue-soft); color: var(--accent-blue);">
                <i class="fa-solid fa-pills" aria-hidden="true"></i>
            </span>
            <div class="min-w-0">
                <p class="text-sm font-semibold" style="color: var(--ink);">Open FP Register</p>
                <p class="text-xs mt-0.5 truncate" style="color: var(--ink-muted);">Active acceptors and follow-up schedule</p>
            </div>
        </a>
        <a href="{{ route('maternal.postnatal.index') }}"
           class="flex items-center gap-3 rounded-xl border p-4 transition hover:bg-black/[0.02] hover:shadow-md"
           style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg" style="background: var(--accent-soft); color: var(--accent);">
                <i class="fa-solid fa-baby" aria-hidden="true"></i>
            </span>
            <div class="min-w-0">
                <p class="text-sm font-semibold" style="color: var(--ink);">Open Postnatal Register</p>
                <p class="text-xs mt-0.5 truncate" style="color: var(--ink-muted);">24h / 7d / 14d / 28d follow-ups</p>
            </div>
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function residentSearch() {
        return {
            query: '',
            results: [],
            loading: false,
            async search() {
                if (this.query.length < 2) { this.results = []; return; }
                this.loading = true;
                try {
                    const response = await fetch(`{{ route('search.patients') }}?query=${encodeURIComponent(this.query)}`);
                    this.results = await response.json();
                } catch (e) {
                    console.error('Search failed:', e);
                    this.results = [];
                }
                this.loading = false;
            },
        };
    }
</script>
@endpush

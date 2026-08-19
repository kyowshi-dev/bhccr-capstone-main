@extends('layouts.app')

@section('title', 'Referrals')

@section('content')
@php
    $statusBadgeStyles = [
        'pending' => 'background:var(--bg-surface);color:var(--ink-muted);border:1px solid var(--border);',
        'completed' => 'background:var(--teal-soft);color:var(--primary);border:1px solid var(--border);',
        'no_show' => 'background:var(--danger-soft);color:var(--danger);border:1px solid var(--border);',
        'cancelled' => 'background:var(--bg-surface-elevated);color:var(--ink-subtle);border:1px solid var(--border);',
    ];
@endphp
<div class="space-y-5 lg:space-y-6 animate-in opacity-0">
    @if (session('success'))
        <div class="rounded-xl border px-4 py-3 text-sm" style="background: var(--teal-soft); border-color: var(--border); color: var(--primary);">
            {{ session('success') }}
        </div>
    @endif
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Outward referrals</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Track referrals to higher-level facilities, update outcomes, and re-print referral slips.</p>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-5 gap-3 lg:gap-4">
        <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
            <p class="text-xs uppercase tracking-wide font-semibold" style="color: var(--ink-muted);">Total</p>
            <p class="mt-2 font-display text-2xl lg:text-3xl font-semibold" style="color: var(--ink);">{{ $totalReferrals }}</p>
        </div>
        <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
            <p class="text-xs uppercase tracking-wide font-semibold" style="color: var(--ink-muted);">This week</p>
            <p class="mt-2 font-display text-2xl lg:text-3xl font-semibold" style="color: var(--ink);">{{ $thisWeekReferrals }}</p>
        </div>
        <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border); border-left: 4px solid var(--primary);">
            <p class="text-xs uppercase tracking-wide font-semibold" style="color: var(--ink-muted);">Completed</p>
            <p class="mt-2 font-display text-2xl lg:text-3xl font-semibold" style="color: var(--primary);">{{ $statusCounts['completed'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border); border-left: 4px solid var(--danger);">
            <p class="text-xs uppercase tracking-wide font-semibold" style="color: var(--ink-muted);">No-show</p>
            <p class="mt-2 font-display text-2xl lg:text-3xl font-semibold" style="color: var(--danger);">{{ $statusCounts['no_show'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl border p-4 lg:p-5 col-span-2 md:col-span-1" style="background: var(--bg-surface); border-color: var(--border); border-left: 4px solid var(--ink-subtle);">
            <p class="text-xs uppercase tracking-wide font-semibold" style="color: var(--ink-muted);">Cancelled</p>
            <p class="mt-2 font-display text-2xl lg:text-3xl font-semibold" style="color: var(--ink-subtle);">{{ $statusCounts['cancelled'] ?? 0 }}</p>
        </div>
    </div>

    <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface-elevated); border-color: var(--border);">
        <form method="GET" action="{{ route('referrals.index') }}" class="grid grid-cols-1 md:grid-cols-[1fr_auto_auto_auto] gap-3 items-end">
            <div class="min-w-0">
                <label for="query" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Search</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none" style="color: var(--ink-subtle);">
                        <i class="fa fa-search" aria-hidden="true"></i>
                    </span>
                    <input id="query" name="query" value="{{ request('query') }}" placeholder="Patient, facility, or notes..."
                           class="w-full pl-10 pr-4 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2"
                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                </div>
            </div>
            <div>
                <label for="status" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Status</label>
                <select id="status" name="status" class="w-full md:w-44 px-3 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2"
                        style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                    <option value="">All statuses</option>
                    @foreach ($statusOptions as $statusOption)
                        <option value="{{ $statusOption }}" @selected(request('status') === $statusOption)>{{ $statusLabels[$statusOption] }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition hover:opacity-90" style="background: var(--primary);">Apply</button>
            @if (request()->hasAny(['query', 'status']))
                <a href="{{ route('referrals.index') }}" class="px-4 py-2.5 rounded-xl border text-sm font-semibold text-center transition hover:bg-black/[0.03]"
                   style="border-color: var(--border); color: var(--ink-muted);">Clear</a>
            @endif
        </form>
    </div>

    <div class="space-y-4">
        @forelse ($referrals as $referral)
            @php
                $status = $referral->status ?? 'pending';
                $badgeStyle = $statusBadgeStyles[$status] ?? $statusBadgeStyles['pending'];
            @endphp
            <div class="rounded-xl border p-4 lg:p-5 transition-all duration-200 hover:shadow-md" style="background: var(--bg-surface); border-color: var(--border);">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-semibold text-lg" style="color: var(--ink);">{{ fullName($referral->patient_last_name, $referral->patient_first_name) }} <span class="text-sm font-medium" style="color: var(--ink-subtle);">({{ \App\Helpers\PatientCode::format((int) $referral->patient_id) }})</span></h2>
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold" style="{{ $badgeStyle }}">{{ $statusLabels[$status] ?? ucfirst($status) }}</span>
                        </div>
                        <p class="text-sm mt-1" style="color: var(--ink-muted);">Referred to <strong>{{ $referral->destination_facility }}</strong></p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold" style="color: var(--ink-muted);">
                        <span>{{ \Carbon\Carbon::parse($referral->created_at)->format('M d, Y g:i A') }}</span>
                        <a href="{{ route('referrals.print', $referral->id) }}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-white transition hover:opacity-90"
                           style="background: var(--primary);">
                            <i class="fa-solid fa-print" aria-hidden="true"></i> Re-print
                        </a>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-4 text-sm" style="color: var(--ink);">
                    <div>
                        <p class="font-semibold text-xs uppercase tracking-wide" style="color: var(--ink-muted);">Pertinent history</p>
                        <p class="mt-2 whitespace-pre-line">{{ $referral->pertinent_history }}</p>
                    </div>
                    <div>
                        <p class="font-semibold text-xs uppercase tracking-wide" style="color: var(--ink-muted);">Actions taken</p>
                        <p class="mt-2 whitespace-pre-line">{{ $referral->actions_taken ?: 'No actions recorded.' }}</p>
                    </div>
                    <div>
                        <p class="font-semibold text-xs uppercase tracking-wide" style="color: var(--ink-muted);">Specific details</p>
                        <p class="mt-2 whitespace-pre-line">{{ $referral->specific_details ?: 'No additional clinical notes.' }}</p>
                    </div>
                </div>
                <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <span class="text-sm" style="color: var(--ink-muted);">Created by {{ fullName($referral->worker_last_name, $referral->worker_first_name) }}</span>
                    <div class="flex flex-wrap items-center gap-3">
                        <form method="POST" action="{{ route('referrals.update-status', $referral->id) }}" class="flex flex-wrap items-center gap-2">
                            @csrf
                            @method('PATCH')
                            <label for="status-{{ $referral->id }}" class="text-xs font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">Update status</label>
                            <select id="status-{{ $referral->id }}" name="status" onchange="this.form.submit()"
                                    class="px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2"
                                    style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                                @foreach ($statusOptions as $statusOption)
                                    <option value="{{ $statusOption }}" @selected($status === $statusOption)>{{ $statusLabels[$statusOption] }}</option>
                                @endforeach
                            </select>
                        </form>
                        <a href="{{ route('consultations.show', $referral->consultation_id) }}" class="text-primary text-sm font-medium hover:underline">View consultation</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-xl border p-10 text-center" style="background: var(--bg-surface); border-color: var(--border);">
                <div class="flex justify-center mb-3"><i class="fa-solid fa-arrow-up-right-from-square text-3xl" style="color: var(--ink-subtle);" aria-hidden="true"></i></div>
                <p class="font-semibold" style="color: var(--ink);">No referrals found</p>
                <p class="mt-2 text-sm" style="color: var(--ink-muted);">Create an outward referral from the consultation modal to see it appear here.</p>
            </div>
        @endforelse
    </div>

    <div class="pt-4">
        <x-pagination :paginator="$referrals" />
    </div>
</div>
@endsection

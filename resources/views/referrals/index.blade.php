@extends('layouts.app')

@section('content')
@php
    $statusBadgeStyles = [
        'pending' => 'background:#f3f4f6;color:#374151;border:1px solid #d1d5db;',
        'completed' => 'background:#dcfce7;color:#166534;border:1px solid #86efac;',
        'no_show' => 'background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;',
        'cancelled' => 'background:#f3f4f6;color:#6b7280;border:1px solid #d1d5db;',
    ];
@endphp
<div class="space-y-5 lg:space-y-6 animate-in opacity-0">
    @if (session('success'))
        <div class="rounded-xl border px-4 py-3 text-sm" style="background: #ecfdf5; border-color: #86efac; color: #166534;">
            {{ session('success') }}
        </div>
    @endif
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Outward referrals</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Track referrals to higher-level facilities, update outcomes, and re-print referral slips.</p>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 lg:gap-4">
        <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
            <p class="text-xs uppercase tracking-wide font-semibold" style="color: var(--ink-muted);">Total</p>
            <p class="mt-2 font-display text-2xl lg:text-3xl font-semibold" style="color: var(--ink);">{{ $totalReferrals }}</p>
        </div>
        <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
            <p class="text-xs uppercase tracking-wide font-semibold" style="color: var(--ink-muted);">This week</p>
            <p class="mt-2 font-display text-2xl lg:text-3xl font-semibold" style="color: var(--ink);">{{ $thisWeekReferrals }}</p>
        </div>
        <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border); border-left: 4px solid #22c55e;">
            <p class="text-xs uppercase tracking-wide font-semibold" style="color: var(--ink-muted);">Completed</p>
            <p class="mt-2 font-display text-2xl lg:text-3xl font-semibold" style="color: #166534;">{{ $statusCounts['completed'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border); border-left: 4px solid #ef4444;">
            <p class="text-xs uppercase tracking-wide font-semibold" style="color: var(--ink-muted);">No-show</p>
            <p class="mt-2 font-display text-2xl lg:text-3xl font-semibold" style="color: #991b1b;">{{ $statusCounts['no_show'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl border p-4 lg:p-5 col-span-2 md:col-span-1" style="background: var(--bg-surface); border-color: var(--border); border-left: 4px solid #9ca3af;">
            <p class="text-xs uppercase tracking-wide font-semibold" style="color: var(--ink-muted);">Cancelled</p>
            <p class="mt-2 font-display text-2xl lg:text-3xl font-semibold" style="color: #6b7280;">{{ $statusCounts['cancelled'] ?? 0 }}</p>
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
                            <h2 class="font-semibold text-lg" style="color: var(--ink);">{{ $referral->patient_last_name }}, {{ ucwords($referral->patient_first_name) }} <span class="text-sm font-medium" style="color: var(--ink-subtle);">(PT{{ str_pad($referral->patient_id, 3, '0', STR_PAD_LEFT) }})</span></h2>
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
                    <span class="text-sm" style="color: var(--ink-muted);">Created by {{ $referral->worker_first_name }} {{ $referral->worker_last_name }}</span>
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
            <div class="rounded-xl border p-6 text-center" style="background: var(--bg-surface); border-color: var(--border);">
                <p class="font-semibold" style="color: var(--ink);">No referrals found</p>
                <p class="mt-2 text-sm" style="color: var(--ink-muted);">Create an outward referral from the consultation modal to see it appear here.</p>
            </div>
        @endforelse
    </div>

    <div class="pt-4">
        {{ $referrals->links() }}
    </div>
</div>
@endsection

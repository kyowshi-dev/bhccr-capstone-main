@props(['records' => collect()])
@php
    $today = \Carbon\Carbon::today();
    $groups = $records->groupBy('patient_id');
@endphp
<div class="rounded-xl border overflow-hidden" style="background: var(--bg-surface-elevated); border-color: var(--border);">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead style="background: var(--teal-soft);">
                <tr>
                    <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);">Patient</th>
                    <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);">Recent doses</th>
                    <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap hidden md:table-cell" style="color: var(--ink-muted);">Status</th>
                    <th class="px-3 lg:px-4 py-2 lg:py-3 text-right text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[var(--border)]">
                @forelse ($groups as $patientId => $group)
                    @php
                        $latest = $group->first();
                        $hasOverdue = $group->contains(fn ($r) => $r->next_due !== null && \Carbon\Carbon::parse($r->next_due)->lt($today));
                        $hasDueToday = $group->contains(fn ($r) => $r->next_due !== null && \Carbon\Carbon::parse($r->next_due)->isSameDay($today));
                        $hasNextDue = $group->contains(fn ($r) => $r->next_due !== null);
                    @endphp
                    <tr class="transition-colors hover:bg-black/[0.02]">
                        <td class="px-3 lg:px-4 py-2 lg:py-3" style="color: var(--ink);">
                            <a href="{{ route('immunizations.patient', (int) $patientId) }}" class="hover:underline font-medium" style="color: var(--primary);">
                                {{ fullName($latest->last_name, $latest->first_name) }}
                            </a>
                        </td>
                        <td class="px-3 lg:px-4 py-2 lg:py-3">
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($group as $r)
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium whitespace-nowrap"
                                          style="background: rgba(0,0,0,0.05); color: var(--ink);">
                                        {{ \Carbon\Carbon::parse($r->date_given)->format('M d') }} · {{ $r->vaccine_name }} · Dose {{ $r->dose_number }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-3 lg:px-4 py-2 lg:py-3 hidden md:table-cell">
                            @if ($hasOverdue)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--danger-soft); color: var(--danger);">
                                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> Overdue
                                </span>
                            @elseif ($hasDueToday)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--accent-blue-soft); color: var(--accent-blue);">
                                    <i class="fa-regular fa-calendar" aria-hidden="true"></i> Due today
                                </span>
                            @elseif ($hasNextDue)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" style="background: rgba(0,0,0,0.06); color: var(--ink-muted);">In progress</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--teal-soft); color: var(--primary);">Up to date</span>
                            @endif
                        </td>
                        <td class="px-3 lg:px-4 py-2 lg:py-3 text-right whitespace-nowrap">
                            <a href="{{ route('immunizations.patient', (int) $patientId) }}" class="text-sm font-medium hover:underline" style="color: var(--primary);">
                                Open
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-3 lg:px-4 py-12 text-center">
                            <x-empty-state icon="fa-solid fa-clock-rotate-left" title="No recent records"
                                           description="Immunization records will appear here once you start recording doses." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

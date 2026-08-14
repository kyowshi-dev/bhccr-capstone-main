@props([
    'patient' => null,
    'schedule' => collect(),
    'statuses' => [],
    'eligibility' => [],
    'schedulesByVaccine' => [],
    'noShowEvents' => [],
    'records' => collect(),
])
@php
    $recordsByVaccine = $records->groupBy('vaccine_id');
@endphp
<div>
    <h2 class="font-display font-semibold text-lg mb-1" style="color: var(--ink);">Immunization schedule</h2>
    <p class="text-sm mb-3" style="color: var(--ink-muted);">Dose numbers advance automatically from prior records.</p>
    <div class="rounded-xl border overflow-hidden" style="background: var(--bg-surface-elevated); border-color: var(--border);">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead style="background: var(--teal-soft);">
                    <tr>
                        <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium" style="color: var(--ink-muted);">Vaccine</th>
                        <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium" style="color: var(--ink-muted);">Status</th>
                        <th class="px-3 lg:px-4 py-2 lg:py-3 text-right text-xs font-medium" style="color: var(--ink-muted);">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--border)]">
                    @forelse ($schedule as $item)
                        @php
                            $vaccineId = $item->vaccine->id;
                            $status = $statuses[$vaccineId] ?? 'waiting';
                            $elig = $eligibility[$vaccineId] ?? ['state' => 'waiting', 'earliest_date' => null, 'requires_override' => false];
                            $givenCount = $recordsByVaccine->get($vaccineId, collect())->count();
                            $nextDose = $givenCount + 1;
                            $nextSchedule = $schedulesByVaccine[$vaccineId] ?? collect();
                            $nextScheduleRow = $nextSchedule->where('dose_number', $nextDose)->first();
                            $requiresTemp = (bool) ($nextScheduleRow->requires_temp ?? false);
                            $noShowEvent = $noShowEvents[$vaccineId] ?? null;
                            $earliestDate = $elig['earliest_date'] ?? null;
                        @endphp
                        <tr class="transition-colors hover:bg-black/[0.02]">
                            <td class="px-3 lg:px-4 py-3" style="color: var(--ink);">
                                <div class="font-medium">{{ $item->vaccine->vaccine_name }}</div>
                                @if ($item->vaccine->vaccine_code)
                                    <div class="text-xs mt-0.5" style="color: var(--ink-muted);">{{ $item->vaccine->vaccine_code }}</div>
                                @endif
                            </td>
                            <td class="px-3 lg:px-4 py-3">
                                @if ($status === 'completed')
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium" style="color: var(--ink-muted);">
                                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Complete
                                    </span>
                                @elseif ($status === 'no_show')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--danger-soft); color: var(--danger);">
                                        <i class="fa-solid fa-user-clock" aria-hidden="true"></i> No-show
                                    </span>
                                @elseif ($status === 'out_of_window')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--amber-soft); color: var(--amber);">
                                        <i class="fa-solid fa-clock" aria-hidden="true"></i> Out of window
                                    </span>
                                @elseif ($status === 'overdue')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--danger-soft); color: var(--danger);">
                                        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> Overdue
                                    </span>
                                @else
                                    <span class="text-xs font-medium" style="color: var(--ink-muted);">Waiting</span>
                                @endif
                            </td>
                            <td class="px-3 lg:px-4 py-3 text-right whitespace-nowrap">
                                @if ($status === 'completed')
                                    <span class="text-xs font-medium" style="color: var(--ink-muted);">Series complete</span>
                                @elseif ($elig['state'] === 'too_early')
                                    <span class="text-xs font-medium" style="color: var(--ink-muted);">
                                        Earliest {{ \Carbon\Carbon::parse($earliestDate)->format('M d, Y') }}
                                    </span>
                                @else
                                    <div class="flex flex-col items-end gap-2">
                                        <div class="inline-flex items-center gap-1.5">
                                            @if ($status === 'no_show' && $noShowEvent)
                                                <form method="POST" action="{{ route('immunizations.no-show') }}" @submit.prevent="confirmClearNoShow($event.target)">
                                                    @csrf
                                                    <input type="hidden" name="no_show" value="0">
                                                    <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                                                    <input type="hidden" name="vaccine_id" value="{{ $vaccineId }}">
                                                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-semibold transition hover:bg-black/[0.03]" style="border-color: var(--border); color: var(--ink-muted);">
                                                        Clear no-show
                                                    </button>
                                                </form>
                                            @endif
                                            <button type="button"
                                                @click="$dispatch('open-administer', {
                                                    vaccineId: {{ $vaccineId }},
                                                    vaccineName: @js($item->vaccine->vaccine_name),
                                                    doseNumber: {{ $nextDose }},
                                                    requiresTemp: {{ $requiresTemp ? 'true' : 'false' }},
                                                    outOfWindow: {{ ($elig['state'] ?? '') === 'out_of_window' ? 'true' : 'false' }}
                                                })"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition hover:shadow-md"
                                                style="background: var(--primary);">
                                                <i class="fa-solid fa-syringe" aria-hidden="true"></i> Administer
                                            </button>
                                        </div>
                                        @if (($elig['state'] ?? '') !== 'out_of_window')
                                            <form method="POST" action="{{ route('immunizations.mark-done', [$patient->id, $vaccineId]) }}" @submit.prevent="confirmMarkDone($event.target)">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1 text-xs font-medium hover:underline" style="color: var(--ink-muted);">
                                                    <i class="fa-solid fa-arrow-right-to-bracket" aria-hidden="true"></i> Mark done elsewhere
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-sm" style="color: var(--ink-muted);">No vaccines in schedule for this age group.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

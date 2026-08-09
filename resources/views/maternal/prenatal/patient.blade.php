@extends('layouts.app')

@section('title', 'Prenatal — '.fullName($patient->last_name, $patient->first_name, $patient->middle_name, $patient->suffix))

@section('content')
@php
    $active = $pregnancies->firstWhere('status', \App\Models\Pregnancy::STATUS_ACTIVE);
    $profile = $patient->maternalProfile;
@endphp

<div class="space-y-5 lg:space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div class="flex items-start gap-3">
            <span class="mt-0.5 header-chip">
                <i class="fa-solid fa-baby-carriage text-lg" aria-hidden="true"></i>
            </span>
            <div>
                <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">{{ fullName($patient->last_name, $patient->first_name, $patient->middle_name, $patient->suffix) }}</h1>
                <p class="text-sm mt-1" style="color: var(--ink-muted);">
                    {{ $patient->sex }}, {{ $patient->age }} y/o · Zone {{ $patient->household?->zone?->zone_number ?? $patient->household?->zone_id ?? '—' }}
                </p>
            </div>
        </div>
        <a href="{{ route('patients.show', $patient->id) }}" class="inline-flex items-center justify-center gap-1.5 rounded-lg border px-4 py-2 text-sm font-semibold transition hover:bg-black/[0.03]"
           style="border-color: var(--border); color: var(--ink-muted);">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Patient profile
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6">
        <div class="lg:col-span-1 space-y-4 lg:space-y-6">
            @if ($active !== null)
                <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="font-display font-semibold text-lg" style="color: var(--ink);">Current pregnancy</h2>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--teal-soft); color: var(--primary);">
                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Active
                        </span>
                    </div>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-xs font-medium" style="color: var(--ink-muted);">Gravida / Para</dt>
                            <dd class="font-medium" style="color: var(--ink);">G{{ $active->gravidity }} P{{ $active->parity }} (T{{ $active->term }} P{{ $active->preterm }} L{{ $active->livebirth }} A{{ $active->abortion }})</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-xs font-medium" style="color: var(--ink-muted);">LMP</dt>
                            <dd class="font-medium" style="color: var(--ink);">{{ $active->lmp?->format('M d, Y') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-xs font-medium" style="color: var(--ink-muted);">EDC</dt>
                            <dd class="font-medium" style="color: var(--ink);">{{ $active->edc?->format('M d, Y') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-xs font-medium" style="color: var(--ink-muted);">AOG</dt>
                            <dd class="font-medium" style="color: var(--ink);">
                                @if ($active->aog_weeks !== null)
                                    {{ $active->aog_weeks }} weeks
                                @elseif ($active->edc !== null)
                                    {{ max(0, \Carbon\Carbon::today()->diffInWeeks(\Carbon\Carbon::parse($active->edc)->subDays(280))) }} weeks
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between items-center">
                            <dt class="text-xs font-medium" style="color: var(--ink-muted);">Syphilis (RPR)</dt>
                            <dd>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold"
                                      style="{{ $active->syphilis_result === 'positive' ? 'background: var(--danger-soft); color: var(--danger);' : 'background: var(--teal-soft); color: var(--primary);' }}">
                                    <i class="fa-solid {{ $active->syphilis_result === 'positive' ? 'fa-triangle-exclamation' : 'fa-circle-check' }}" aria-hidden="true"></i>
                                    {{ ucfirst($active->syphilis_result) }}
                                </span>
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-xs font-medium" style="color: var(--ink-muted);">Penicillin</dt>
                            <dd class="font-medium" style="color: var(--ink);">{{ ucfirst($active->penicillin) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-xs font-medium" style="color: var(--ink-muted);">TT dose</dt>
                            <dd class="font-medium" style="color: var(--ink);">{{ $active->tt_date?->format('M d, Y') ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-xs font-medium" style="color: var(--ink-muted);">Iron taken</dt>
                            <dd class="font-medium" style="color: var(--ink);">{{ $active->iron_taken ? 'Yes' : 'No' }}</dd>
                        </div>
                        @if ($active->others)
                            <div class="flex justify-between items-start gap-3">
                                <dt class="text-xs font-medium" style="color: var(--ink-muted);">Others</dt>
                                <dd class="text-xs text-right" style="color: var(--ink-muted);">{{ $active->others }}</dd>
                            </div>
                        @endif
                    </dl>
                    <div class="mt-4 pt-3 border-t flex flex-wrap gap-2" style="border-color: var(--border);">
                        <button type="button" @click="$dispatch('open-edit-pregnancy')"
                                class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-semibold transition hover:bg-black/[0.05]"
                                style="border-color: var(--border); color: var(--accent-blue);">
                            <i class="fa-solid fa-pen" aria-hidden="true"></i> Edit pregnancy
                        </button>
                        <a href="{{ route('maternal.pregnancies.print', $active->id) }}" target="_blank"
                           class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-semibold transition hover:bg-black/[0.05]"
                           style="border-color: var(--border); color: var(--ink-muted);">
                            <i class="fa-solid fa-print" aria-hidden="true"></i> Print
                        </a>
                    </div>
                </div>
            @else
                <div class="rounded-xl border border-dashed p-6 text-center" style="background: var(--bg-surface); border-color: var(--border);">
                    <i class="fa-solid fa-baby-carriage text-2xl mb-2" style="color: var(--ink-subtle);" aria-hidden="true"></i>
                    <p class="font-semibold text-sm" style="color: var(--ink);">No active pregnancy</p>
                    <p class="text-xs mt-1 mb-4" style="color: var(--ink-muted);">Register the current pregnancy to start tracking prenatal visits.</p>
                    <button type="button" @click="$dispatch('open-register-pregnancy')"
                            class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition hover:shadow-md w-full"
                            style="background: var(--primary);">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i> Register pregnancy
                    </button>
                </div>
            @endif

            <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-display font-semibold text-lg" style="color: var(--ink);">Obstetric history</h2>
                    <button type="button" @click="$dispatch('open-profile')"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition hover:bg-black/[0.05]"
                            style="color: var(--accent-blue);">
                        <i class="fa-solid fa-pen" aria-hidden="true"></i> {{ $profile !== null ? 'Edit' : 'Add' }}
                    </button>
                </div>
                @if ($profile === null)
                    <p class="text-sm" style="color: var(--ink-subtle);">No obstetric history recorded yet.</p>
                @else
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-xs font-medium" style="color: var(--ink-muted);">Menarche</dt>
                            <dd class="font-medium" style="color: var(--ink);">{{ $profile->menarche_age ?? '—' }} y/o</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-xs font-medium" style="color: var(--ink-muted);">Menstrual period</dt>
                            <dd class="font-medium" style="color: var(--ink);">
                                {{ $profile->period_duration_days !== null && $profile->cycle_interval_days !== null
                                    ? $profile->period_duration_days.'d / '.$profile->cycle_interval_days.'d cycle'
                                    : '—' }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-xs font-medium" style="color: var(--ink-muted);">Sexual onset</dt>
                            <dd class="font-medium" style="color: var(--ink);">{{ $profile->onset_sexual_intercourse_age ?? '—' }} y/o</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-xs font-medium" style="color: var(--ink-muted);">Birth control</dt>
                            <dd class="font-medium" style="color: var(--ink);">{{ $profile->birth_control_method ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-xs font-medium" style="color: var(--ink-muted);">Menopause</dt>
                            <dd class="font-medium" style="color: var(--ink);">{{ ucfirst($profile->menopause) }}</dd>
                        </div>
                    </dl>
                @endif
            </div>
        </div>

        <div class="lg:col-span-2 space-y-4 lg:space-y-6">
            @if ($active !== null)
                <div class="rounded-xl border overflow-hidden" style="background: var(--bg-surface); border-color: var(--border);">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 lg:p-5">
                        <div>
                            <h2 class="font-display font-semibold text-lg mb-3" style="color: var(--ink);">Record prenatal visit</h2>
                            <form method="POST" action="{{ route('maternal.prenatal.visits.store', $active->id) }}" class="space-y-3">
                                @csrf
                                <div>
                                    <label for="visit_date" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Visit date <span style="color: var(--danger);">*</span></label>
                                    <input id="visit_date" type="date" name="visit_date" value="{{ old('visit_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}"
                                           class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                    @error('visit_date') <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p> @enderror
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label for="fundic_height_cm" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Fundic height (cm)</label>
                                        <input id="fundic_height_cm" type="number" step="0.1" min="0" max="99.9" name="fundic_height_cm" value="{{ old('fundic_height_cm') }}"
                                               class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                                               style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                    </div>
                                    <div>
                                        <label for="fetal_heart_tone_bpm" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">FHT (bpm)</label>
                                        <input id="fetal_heart_tone_bpm" type="number" min="60" max="220" name="fetal_heart_tone_bpm" value="{{ old('fetal_heart_tone_bpm') }}"
                                               class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                                               style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                    </div>
                                </div>
                                <div>
                                    <label for="next_visit_date" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Next visit</label>
                                    <input id="next_visit_date" type="date" name="next_visit_date" value="{{ old('next_visit_date') }}"
                                           class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                </div>
                                @include('maternal.partials.consultation-select', [
                                    'fieldName' => 'consultation_id',
                                    'consultations' => $consultations,
                                    'selected' => old('consultation_id'),
                                ])
                                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition hover:shadow-md"
                                        style="background: var(--primary);">
                                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save visit
                                </button>
                            </form>
                        </div>
                        <div>
                            <h2 class="font-display font-semibold text-lg mb-3" style="color: var(--ink);">Visit history</h2>
                            @if ($active->visits->isEmpty())
                                <p class="text-sm" style="color: var(--ink-subtle);">No prenatal visits recorded yet.</p>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm text-left">
                                        <thead class="border-b" style="background: var(--teal-soft);">
                                            <tr class="text-xs uppercase tracking-wide" style="color: var(--ink-muted);">
                                                <th class="px-3 py-2 font-semibold whitespace-nowrap">Date</th>
                                                <th class="px-3 py-2 font-semibold whitespace-nowrap">FH (cm)</th>
                                                <th class="px-3 py-2 font-semibold whitespace-nowrap">FHT (bpm)</th>
                                                <th class="px-3 py-2 font-semibold whitespace-nowrap">Next visit</th>
                                                <th class="px-3 py-2 font-semibold whitespace-nowrap"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y" style="border-color: var(--border);">
                                            @foreach ($active->visits->sortByDesc('visit_date') as $visit)
                                                <tr class="hover:bg-black/[0.03]">
                                                    <td class="px-3 py-2 whitespace-nowrap font-medium" style="color: var(--ink);">{{ $visit->visit_date->format('M d, Y') }}</td>
                                                    <td class="px-3 py-2" style="color: var(--ink-muted);">{{ $visit->fundic_height_cm ?? '—' }}</td>
                                                    <td class="px-3 py-2" style="color: var(--ink-muted);">{{ $visit->fetal_heart_tone_bpm ?? '—' }}</td>
                                                    <td class="px-3 py-2 whitespace-nowrap" style="color: var(--ink-muted);">{{ $visit->next_visit_date?->format('M d, Y') ?? '—' }}</td>
                                                    <td class="px-3 py-2 text-right">
                                                        <button type="button" @click="$dispatch('open-edit-visit', {
                                                            id: {{ $visit->id }},
                                                            visit_date: '{{ $visit->visit_date?->format('Y-m-d') }}',
                                                            fundic_height_cm: {{ $visit->fundic_height_cm ?? 'null' }},
                                                            fetal_heart_tone_bpm: {{ $visit->fetal_heart_tone_bpm ?? 'null' }},
                                                            next_visit_date: '{{ $visit->next_visit_date?->format('Y-m-d') }}',
                                                            consultation_id: {{ $visit->consultation_id ?? 'null' }}
                                                        })"
                                                                class="text-xs font-semibold hover:underline" style="color: var(--accent-blue);">Edit</button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <div class="rounded-xl border overflow-hidden" style="background: var(--bg-surface); border-color: var(--border);">
                <h2 class="font-display font-semibold text-lg px-4 pt-4" style="color: var(--ink);">Pregnancy history</h2>
                @if ($pregnancies->isEmpty())
                    <p class="px-4 py-6 text-sm" style="color: var(--ink-subtle);">No pregnancies recorded.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left mt-2">
                            <thead class="border-b" style="background: var(--teal-soft);">
                                <tr class="text-xs uppercase tracking-wide" style="color: var(--ink-muted);">
                                    <th class="px-4 py-2.5 font-semibold whitespace-nowrap">LMP</th>
                                    <th class="px-4 py-2.5 font-semibold whitespace-nowrap">EDC</th>
                                    <th class="px-4 py-2.5 font-semibold whitespace-nowrap">Visits</th>
                                    <th class="px-4 py-2.5 font-semibold whitespace-nowrap">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" style="border-color: var(--border);">
                                @foreach ($pregnancies as $preg)
                                    <tr class="hover:bg-black/[0.03]">
                                        <td class="px-4 py-2.5 whitespace-nowrap font-medium" style="color: var(--ink);">{{ $preg->lmp?->format('M d, Y') }}</td>
                                        <td class="px-4 py-2.5 whitespace-nowrap" style="color: var(--ink-muted);">{{ $preg->edc?->format('M d, Y') ?? '—' }}</td>
                                        <td class="px-4 py-2.5" style="color: var(--ink-muted);">{{ $preg->visits->count() }}</td>
                                        <td class="px-4 py-2.5">
                                            @if ($preg->status === \App\Models\Pregnancy::STATUS_ACTIVE)
                                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold" style="background: var(--teal-soft); color: var(--primary);">Active</span>
                                            @elseif ($preg->status === \App\Models\Pregnancy::STATUS_DELIVERED)
                                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold" style="background: var(--accent-blue-soft); color: var(--accent-blue);">Delivered</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold" style="background: var(--amber-soft); color: var(--amber);">Closed</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<x-modal name="edit-visit-modal" title="Edit prenatal visit"
         x-data="visitEditor()" x-on:open-edit-visit.window="open = true; setVisit($event.detail)" x-on:close.window="open = false">
    <form method="POST" x-bind:action="`{{ url('prenatal-visits') }}/${visit.id}`" class="space-y-3">
        @csrf
        @method('PUT')
        <div>
            <label for="ev_visit_date" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Visit date <span style="color: var(--danger);">*</span></label>
            <input id="ev_visit_date" type="date" name="visit_date" max="{{ now()->toDateString() }}" x-model="visit.visit_date"
                   class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                   style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label for="ev_fundic_height_cm" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Fundic height (cm)</label>
                <input id="ev_fundic_height_cm" type="number" step="0.1" min="0" max="99.9" name="fundic_height_cm" x-model.number="visit.fundic_height_cm"
                       class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
            </div>
            <div>
                <label for="ev_fetal_heart_tone_bpm" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">FHT (bpm)</label>
                <input id="ev_fetal_heart_tone_bpm" type="number" min="60" max="220" name="fetal_heart_tone_bpm" x-model.number="visit.fetal_heart_tone_bpm"
                       class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
            </div>
        </div>
        <div>
            <label for="ev_next_visit_date" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Next visit</label>
            <input id="ev_next_visit_date" type="date" name="next_visit_date" x-model="visit.next_visit_date"
                   class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                   style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
        </div>
        @include('maternal.partials.consultation-select', [
            'fieldName' => 'consultation_id',
            'consultations' => $consultations,
            'selected' => $visit->consultation_id ?? null,
            'xModel' => 'visit.consultation_id',
        ])
        <div class="flex justify-end gap-2 pt-1">
            <button type="button" @click="$dispatch('close')" class="rounded-lg border px-4 py-2 text-sm font-semibold transition hover:bg-black/[0.03]"
                    style="border-color: var(--border); color: var(--ink-muted);">Cancel</button>
            <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold text-white transition hover:shadow-md" style="background: var(--primary);">Save changes</button>
        </div>
    </form>
</x-modal>

<x-modal name="register-pregnancy-modal" title="Register pregnancy"
         x-on:open-register-pregnancy.window="open = true" x-on:close.window="open = false">
    <form method="POST" action="{{ route('maternal.pregnancies.store', $patient->id) }}" class="space-y-3">
        @csrf
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            @foreach ([['gravidity', 'Gravidity'], ['parity', 'Parity'], ['term', 'Term'], ['preterm', 'Preterm'], ['livebirth', 'Live births'], ['abortion', 'Abortions']] as [$field, $label])
                <div>
                    <label for="reg_{{ $field }}" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">{{ $label }} <span style="color: var(--danger);">*</span></label>
                    <input id="reg_{{ $field }}" type="number" min="0" max="25" name="{{ $field }}" value="{{ old($field) }}"
                           class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                    @error($field) <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p> @enderror
                </div>
            @endforeach
        </div>
        <div x-data="edcCalculator()" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label for="lmp" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">LMP <span style="color: var(--danger);">*</span></label>
                <input id="lmp" type="date" name="lmp" max="{{ now()->toDateString() }}" value="{{ old('lmp') }}" x-model="lmp"
                       class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                @error('lmp') <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="edc" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">EDC <span class="text-xs font-normal" style="color: var(--ink-subtle);">(auto: LMP + 280d, editable)</span></label>
                <input id="edc" type="date" name="edc" value="{{ old('edc') }}" x-model="edc"
                       class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                <p class="mt-1 text-xs" style="color: var(--ink-subtle);" x-text="edcHint"></p>
                @error('edc') <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label for="syphilis_result" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Syphilis (RPR) <span style="color: var(--danger);">*</span></label>
                <select id="syphilis_result" name="syphilis_result" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                        style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                    <option value="negative" @selected(old('syphilis_result', 'negative') === 'negative')>Negative</option>
                    <option value="positive" @selected(old('syphilis_result') === 'positive')>Positive</option>
                </select>
                @error('syphilis_result') <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="penicillin" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Penicillin <span style="color: var(--danger);">*</span></label>
                <select id="penicillin" name="penicillin" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                        style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                    <option value="no" @selected(old('penicillin', 'no') === 'no')>No</option>
                    <option value="yes" @selected(old('penicillin') === 'yes')>Yes</option>
                </select>
                @error('penicillin') <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div>
                <label for="tt_date" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">TT dose date</label>
                <input id="tt_date" type="date" name="tt_date" max="{{ now()->toDateString() }}" value="{{ old('tt_date') }}"
                       class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                @error('tt_date') <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="iron_taken" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Iron taken</label>
                <select id="iron_taken" name="iron_taken" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                        style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                    <option value="0" @selected((int) old('iron_taken', 0) === 0)>No</option>
                    <option value="1" @selected((int) old('iron_taken') === 1)>Yes</option>
                </select>
            </div>
            <div>
                <label for="aog_weeks" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">AOG (weeks)</label>
                <input id="aog_weeks" type="number" min="0" max="45" name="aog_weeks" value="{{ old('aog_weeks') }}"
                       class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
            </div>
        </div>
        <div>
            <label for="others" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Others</label>
            <textarea id="others" name="others" rows="2" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                      style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">{{ old('others') }}</textarea>
        </div>
        <div class="flex justify-end gap-2 pt-1">
            <button type="button" @click="$dispatch('close')" class="rounded-lg border px-4 py-2 text-sm font-semibold transition hover:bg-black/[0.03]"
                    style="border-color: var(--border); color: var(--ink-muted);">Cancel</button>
            <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold text-white transition hover:shadow-md" style="background: var(--primary);">Register</button>
        </div>
    </form>
</x-modal>

@if ($active !== null)
    <x-modal name="edit-pregnancy-modal" title="Edit pregnancy"
             x-on:open-edit-pregnancy.window="open = true" x-on:close.window="open = false">
        <form method="POST" action="{{ route('maternal.pregnancies.update', $active->id) }}" class="space-y-3">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @foreach ([['gravidity', 'Gravidity'], ['parity', 'Parity'], ['term', 'Term'], ['preterm', 'Preterm'], ['livebirth', 'Live births'], ['abortion', 'Abortions']] as [$field, $label])
                    <div>
                        <label for="upd_{{ $field }}" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">{{ $label }} <span style="color: var(--danger);">*</span></label>
                        <input id="upd_{{ $field }}" type="number" min="0" max="25" name="{{ $field }}" value="{{ old($field, $active->{$field}) }}"
                               class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                               style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                    </div>
                @endforeach
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="upd_lmp" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">LMP <span style="color: var(--danger);">*</span></label>
                    <input id="upd_lmp" type="date" name="lmp" max="{{ now()->toDateString() }}" value="{{ old('lmp', $active->lmp?->format('Y-m-d')) }}"
                           class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                </div>
                <div>
                    <label for="upd_edc" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">EDC</label>
                    <input id="upd_edc" type="date" name="edc" value="{{ old('edc', $active->edc?->format('Y-m-d')) }}"
                           class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <div>
                    <label for="upd_syphilis_result" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Syphilis (RPR)</label>
                    <select id="upd_syphilis_result" name="syphilis_result" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                            style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                        <option value="negative" @selected($active->syphilis_result === 'negative')>Negative</option>
                        <option value="positive" @selected($active->syphilis_result === 'positive')>Positive</option>
                    </select>
                </div>
                <div>
                    <label for="upd_penicillin" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Penicillin</label>
                    <select id="upd_penicillin" name="penicillin" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                            style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                        <option value="no" @selected($active->penicillin === 'no')>No</option>
                        <option value="yes" @selected($active->penicillin === 'yes')>Yes</option>
                    </select>
                </div>
                <div>
                    <label for="upd_tt_date" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">TT dose date</label>
                    <input id="upd_tt_date" type="date" name="tt_date" max="{{ now()->toDateString() }}" value="{{ old('tt_date', $active->tt_date?->format('Y-m-d')) }}"
                           class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                </div>
            </div>
            <div>
                <label for="upd_others" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Others</label>
                <textarea id="upd_others" name="others" rows="2" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                          style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">{{ old('others', $active->others) }}</textarea>
            </div>
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" @click="$dispatch('close')" class="rounded-lg border px-4 py-2 text-sm font-semibold transition hover:bg-black/[0.03]"
                        style="border-color: var(--border); color: var(--ink-muted);">Cancel</button>
                <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold text-white transition hover:shadow-md" style="background: var(--primary);">Save changes</button>
            </div>
        </form>
    </x-modal>
@endif

<x-modal name="profile-modal" title="Obstetric history"
         x-on:open-profile.window="open = true" x-on:close.window="open = false">
    <form method="POST" action="{{ route('maternal.profile.update', $patient->id) }}" class="space-y-3">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div>
                <label for="menarche_age" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Menarche (age)</label>
                <input id="menarche_age" type="number" min="0" max="99" name="menarche_age" value="{{ old('menarche_age', $profile?->menarche_age) }}"
                       class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
            </div>
            <div>
                <label for="period_duration_days" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Period length (days)</label>
                <input id="period_duration_days" type="number" min="1" max="30" name="period_duration_days" value="{{ old('period_duration_days', $profile?->period_duration_days) }}"
                       class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
            </div>
            <div>
                <label for="cycle_interval_days" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Cycle (days)</label>
                <input id="cycle_interval_days" type="number" min="1" max="120" name="cycle_interval_days" value="{{ old('cycle_interval_days', $profile?->cycle_interval_days) }}"
                       class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label for="onset_sexual_intercourse_age" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Sexual onset (age)</label>
                <input id="onset_sexual_intercourse_age" type="number" min="0" max="99" name="onset_sexual_intercourse_age" value="{{ old('onset_sexual_intercourse_age', $profile?->onset_sexual_intercourse_age) }}"
                       class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
            </div>
            <div>
                <label for="birth_control_method" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Birth control method</label>
                <select id="birth_control_method" name="birth_control_method" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                        style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                    <option value="">—</option>
                    @foreach (\App\Models\MaternalProfile::BIRTH_CONTROL_METHODS as $option)
                        <option value="{{ $option }}" @selected(old('birth_control_method', $profile?->birth_control_method) === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label for="menopause" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Menopause <span style="color: var(--danger);">*</span></label>
            <select id="menopause" name="menopause" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                    style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                <option value="no" @selected(old('menopause', $profile?->menopause ?? 'no') === 'no')>No</option>
                <option value="yes" @selected(old('menopause', $profile?->menopause ?? 'no') === 'yes')>Yes</option>
            </select>
            @error('menopause') <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p> @enderror
        </div>
        <div class="flex justify-end gap-2 pt-1">
            <button type="button" @click="$dispatch('close')" class="rounded-lg border px-4 py-2 text-sm font-semibold transition hover:bg-black/[0.03]"
                    style="border-color: var(--border); color: var(--ink-muted);">Cancel</button>
            <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold text-white transition hover:shadow-md" style="background: var(--primary);">Save</button>
        </div>
    </form>
</x-modal>

<script>
    function visitEditor() {
        return {
            visit: { id: null, visit_date: '', fundic_height_cm: null, fetal_heart_tone_bpm: null, next_visit_date: '', consultation_id: null },
            setVisit(d) {
                this.visit = {
                    id: d.id,
                    visit_date: d.visit_date || '',
                    fundic_height_cm: d.fundic_height_cm ?? null,
                    fetal_heart_tone_bpm: d.fetal_heart_tone_bpm ?? null,
                    next_visit_date: d.next_visit_date || '',
                    consultation_id: d.consultation_id ?? null
                };
            }
        };
    }

    function edcCalculator() {
        return {
            lmp: '{{ old('lmp') }}',
            edc: '{{ old('edc') }}',
            get edcHint() {
                if (! this.lmp) return '';
                const d = new Date(this.lmp + 'T00:00:00');
                d.setDate(d.getDate() + 280);
                const iso = d.toISOString().slice(0, 10);
                if (! this.edc) this.edc = iso;
                return 'Suggested EDC: ' + iso;
            }
        };
    }
</script>
@endsection

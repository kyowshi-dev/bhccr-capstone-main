@extends('layouts.app')

@section('title', 'Postnatal — '.fullName($patient->last_name, $patient->first_name, $patient->middle_name, $patient->suffix))

@section('content')
@php
    $current = $records->first();
@endphp

<div class="space-y-5 lg:space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div class="flex items-start gap-3">
            <span class="mt-0.5 header-chip">
                <i class="fa-solid fa-child-reaching text-lg" aria-hidden="true"></i>
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
            @if ($current !== null)
                <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="font-display font-semibold text-lg" style="color: var(--ink);">Latest delivery</h2>
                        <button type="button" @click="$dispatch('open-edit-postnatal')"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition hover:bg-black/[0.05]"
                                style="color: var(--accent-blue);">
                            <i class="fa-solid fa-pen" aria-hidden="true"></i> Edit
                        </button>
                    </div>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-xs font-medium" style="color: var(--ink-muted);">Delivered</dt>
                            <dd class="font-medium" style="color: var(--ink);">{{ $current->delivery_date->format('M d, Y') }} · {{ \Carbon\Carbon::parse($current->delivery_time)->format('g:i A') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-xs font-medium" style="color: var(--ink-muted);">Outcome</dt>
                            <dd class="font-medium" style="color: var(--ink);">{{ \App\Models\PostnatalRecord::OUTCOMES[$current->pregnancy_outcome] ?? $current->pregnancy_outcome }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-xs font-medium" style="color: var(--ink-muted);">Place / Mode</dt>
                            <dd class="font-medium text-right" style="color: var(--ink);">
                                {{ \App\Models\PostnatalRecord::PLACES[$current->place_delivered] ?? $current->place_delivered }}
                                · {{ \App\Models\PostnatalRecord::MODES[$current->mode_of_delivery] ?? $current->mode_of_delivery }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-xs font-medium" style="color: var(--ink-muted);">Attendant</dt>
                            <dd class="font-medium" style="color: var(--ink);">{{ \App\Models\PostnatalRecord::ATTENDANTS[$current->attendant_at_birth] ?? $current->attendant_at_birth }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-xs font-medium" style="color: var(--ink-muted);">Prenatal visits</dt>
                            <dd class="font-medium" style="color: var(--ink);">{{ $current->prenatal_visits_completed ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-xs font-medium" style="color: var(--ink-muted);">Breastfeeding</dt>
                            <dd class="font-medium" style="color: var(--ink);">{{ $current->breastfeeding_date->format('M d, Y') }} · {{ \Carbon\Carbon::parse($current->breastfeeding_time)->format('g:i A') }}</dd>
                        </div>
                    </dl>

                    @if (! empty($current->danger_signs_mother))
                        <div class="mt-4 rounded-lg p-3 text-xs font-semibold" style="background: var(--amber-soft); color: var(--amber);">
                            <i class="fa-solid fa-triangle-exclamation mr-1" aria-hidden="true"></i> Mother danger signs: {{ implode(', ', $current->danger_signs_mother) }}
                        </div>
                    @endif
                    @if (! empty($current->danger_signs_baby))
                        <div class="mt-2 rounded-lg p-3 text-xs font-semibold" style="background: var(--danger-soft); color: var(--danger);">
                            <i class="fa-solid fa-circle-exclamation mr-1" aria-hidden="true"></i> Baby danger signs: {{ implode(', ', $current->danger_signs_baby) }}
                        </div>
                    @endif

                    @if ($current->childPatient !== null)
                        <div class="mt-4 pt-3 border-t flex items-center justify-between" style="border-color: var(--border);">
                            <div class="text-sm">
                                <p class="text-xs font-medium" style="color: var(--ink-muted);">Newborn enrolled</p>
                                <p class="font-medium" style="color: var(--ink);">{{ fullName($current->childPatient->last_name, $current->childPatient->first_name, $current->childPatient->middle_name, $current->childPatient->suffix) }}</p>
                            </div>
                            <a href="{{ route('immunizations.patient', $current->childPatient->id) }}" class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold transition hover:bg-black/[0.05]"
                               style="border-color: var(--border); color: var(--accent-blue);">
                                <i class="fa-solid fa-syringe" aria-hidden="true"></i> Immunizations
                            </a>
                        </div>
                    @endif

                    <div class="mt-4 pt-3 border-t flex flex-wrap gap-2" style="border-color: var(--border);">
                        <a href="{{ route('maternal.postnatal.print', $current->id) }}" target="_blank"
                           class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-semibold transition hover:bg-black/[0.05]"
                           style="border-color: var(--border); color: var(--ink-muted);">
                            <i class="fa-solid fa-print" aria-hidden="true"></i> Print
                        </a>
                    </div>
                </div>

                <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
                    <h2 class="font-display font-semibold text-lg mb-3" style="color: var(--ink);">Postpartum schedule</h2>
                    @php
                        $slots = [
                            ['col' => 'postpartum_24h_date', 'window' => 1, 'label' => '24 hours'],
                            ['col' => 'postpartum_7d_date', 'window' => 7, 'label' => '7 days'],
                            ['col' => 'postpartum_14d_date', 'window' => 14, 'label' => '14 days'],
                            ['col' => 'postpartum_28d_date', 'window' => 28, 'label' => '28 days'],
                        ];
                    @endphp
                    <ul class="space-y-2">
                        @foreach ($slots as $slot)
                            @php
                                $targetDate = \Carbon\Carbon::parse($current->delivery_date)->addDays($slot['window']);
                                $done = $current->{$slot['col']} !== null;
                                $overdue = ! $done && $targetDate->lt(\Carbon\Carbon::today());
                            @endphp
                            <li class="flex items-center justify-between rounded-lg border px-3 py-2.5 text-sm" style="border-color: var(--border);">
                                <span class="flex items-center gap-2 font-medium" style="color: var(--ink);">
                                    <i class="fa-solid {{ $done ? 'fa-circle-check' : ($overdue ? 'fa-circle-exclamation' : 'fa-regular fa-calendar') }}"
                                       aria-hidden="true" style="color: {{ $done ? 'var(--primary)' : ($overdue ? 'var(--danger)' : 'var(--accent-blue)') }};"></i>
                                    {{ $slot['label'] }} visit
                                </span>
                                @if ($done)
                                    <span class="text-xs font-semibold" style="color: var(--primary);">{{ \Carbon\Carbon::parse($current->{$slot['col']})->format('M d, Y') }}</span>
                                @else
                                    <span class="text-xs font-semibold {{ $overdue ? '' : '' }}" style="color: {{ $overdue ? 'var(--danger)' : 'var(--ink-muted)' }};">
                                        {{ $overdue ? 'Overdue · ' : 'Due · ' }}{{ $targetDate->format('M d, Y') }}
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <form method="POST" action="{{ route('maternal.postnatal.complete-visit', $current->id) }}" class="mt-3">
                        @csrf
                        <div>
                            <label for="pp_slot" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Record completed visit</label>
                            <div class="flex gap-2">
                                <select id="pp_slot" name="slot" class="flex-1 rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                                        style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                    @foreach ($slots as $slot)
                                        @if ($current->{$slot['col']} === null)
                                            <option value="{{ $slot['col'] }}">{{ $slot['label'] }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <input type="date" name="date" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}"
                                       class="rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold text-white transition hover:shadow-md" style="background: var(--primary);">Save</button>
                            </div>
                            @if ($errors->has('slot') || $errors->has('date'))
                                <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $errors->first('slot') ?: $errors->first('date') }}</p>
                            @endif
                        </div>
                    </form>
                </div>
            @else
                <div class="rounded-xl border border-dashed p-6 text-center" style="background: var(--bg-surface); border-color: var(--border);">
                    <i class="fa-solid fa-child-reaching text-2xl mb-2" style="color: var(--ink-subtle);" aria-hidden="true"></i>
                    <p class="font-semibold text-sm" style="color: var(--ink);">No postnatal record</p>
                    <p class="text-xs mt-1 mb-4" style="color: var(--ink-muted);">Record the delivery to start the postpartum schedule.</p>
                    <button type="button" @click="$dispatch('open-store-postnatal')"
                            class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition hover:shadow-md w-full"
                            style="background: var(--primary);">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i> Record delivery
                    </button>
                </div>
            @endif
        </div>

        <div class="lg:col-span-2 space-y-4 lg:space-y-6">
            @if ($records->isEmpty())
                <div class="rounded-xl border overflow-hidden" style="background: var(--bg-surface); border-color: var(--border);">
                    <h2 class="font-display font-semibold text-lg px-4 pt-4" style="color: var(--ink);">Delivery history</h2>
                    <p class="px-4 py-6 text-sm" style="color: var(--ink-subtle);">No deliveries recorded for this patient.</p>
                </div>
            @else
                <div class="rounded-xl border overflow-hidden" style="background: var(--bg-surface); border-color: var(--border);">
                    <h2 class="font-display font-semibold text-lg px-4 pt-4" style="color: var(--ink);">Delivery history</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left mt-2">
                            <thead class="border-b" style="background: var(--teal-soft);">
                                <tr class="text-xs uppercase tracking-wide" style="color: var(--ink-muted);">
                                    <th class="px-4 py-2.5 font-semibold whitespace-nowrap">Delivered</th>
                                    <th class="px-4 py-2.5 font-semibold whitespace-nowrap">Outcome</th>
                                    <th class="px-4 py-2.5 font-semibold whitespace-nowrap hidden md:table-cell">Mode</th>
                                    <th class="px-4 py-2.5 font-semibold whitespace-nowrap">Baby</th>
                                    <th class="px-4 py-2.5 font-semibold whitespace-nowrap"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" style="border-color: var(--border);">
                                @foreach ($records as $record)
                                    <tr class="hover:bg-black/[0.03]">
                                        <td class="px-4 py-2.5 whitespace-nowrap font-medium" style="color: var(--ink);">{{ $record->delivery_date->format('M d, Y') }}</td>
                                        <td class="px-4 py-2.5" style="color: var(--ink-muted);">{{ \App\Models\PostnatalRecord::OUTCOMES[$record->pregnancy_outcome] ?? $record->pregnancy_outcome }}</td>
                                        <td class="px-4 py-2.5 hidden md:table-cell" style="color: var(--ink-muted);">{{ \App\Models\PostnatalRecord::MODES[$record->mode_of_delivery] ?? $record->mode_of_delivery }}</td>
                                        <td class="px-4 py-2.5" style="color: var(--ink-muted);">
                                            @if ($record->pregnancy_outcome === \App\Models\PostnatalRecord::OUTCOME_LIVE_BIRTH)
                                                {{ $record->child_first_name }} {{ $record->child_last_name }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5 text-right">
                                            <a href="{{ route('maternal.postnatal.print', $record->id) }}" target="_blank" class="text-xs font-semibold hover:underline" style="color: var(--accent-blue);">Print</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<x-modal name="store-postnatal-modal" title="Record delivery"
         x-on:open-store-postnatal.window="open = true" x-on:close.window="open = false">
    <form method="POST" action="{{ route('maternal.postnatal.store', $patient->id) }}" class="space-y-3">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label for="pn_pregnancy_id" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Link pregnancy (optional)</label>
                <select id="pn_pregnancy_id" name="pregnancy_id" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                        style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                    <option value="">None</option>
                    @foreach ($activePregnancies as $preg)
                        <option value="{{ $preg->id }}" @selected(old('pregnancy_id') == $preg->id)>
                            LMP {{ $preg->lmp?->format('M d, Y') }} · EDC {{ $preg->edc?->format('M d, Y') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="pn_pregnancy_outcome" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Outcome <span style="color: var(--danger);">*</span></label>
                <select id="pn_pregnancy_outcome" name="pregnancy_outcome" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                        style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                    @foreach (\App\Models\PostnatalRecord::OUTCOMES as $value => $label)
                        <option value="{{ $value }}" @selected(old('pregnancy_outcome') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('pregnancy_outcome') <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div>
                <label for="pn_place_delivered" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Place <span style="color: var(--danger);">*</span></label>
                <select id="pn_place_delivered" name="place_delivered" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                        style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                    @foreach (\App\Models\PostnatalRecord::PLACES as $value => $label)
                        <option value="{{ $value }}" @selected(old('place_delivered') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="pn_mode_of_delivery" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Mode <span style="color: var(--danger);">*</span></label>
                <select id="pn_mode_of_delivery" name="mode_of_delivery" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                        style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                    @foreach (\App\Models\PostnatalRecord::MODES as $value => $label)
                        <option value="{{ $value }}" @selected(old('mode_of_delivery') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="pn_attendant_at_birth" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Attendant <span style="color: var(--danger);">*</span></label>
                <select id="pn_attendant_at_birth" name="attendant_at_birth" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                        style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                    @foreach (\App\Models\PostnatalRecord::ATTENDANTS as $value => $label)
                        <option value="{{ $value }}" @selected(old('attendant_at_birth') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div>
                <label for="pn_delivery_date" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Delivery date <span style="color: var(--danger);">*</span></label>
                <input id="pn_delivery_date" type="date" name="delivery_date" max="{{ now()->toDateString() }}" value="{{ old('delivery_date', now()->toDateString()) }}"
                       class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
            </div>
            <div>
                <label for="pn_delivery_time" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Time <span style="color: var(--danger);">*</span></label>
                <input id="pn_delivery_time" type="time" name="delivery_time" value="{{ old('delivery_time') }}"
                       class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
            </div>
            <div>
                <label for="pn_bf_date" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Breastfeeding date <span style="color: var(--danger);">*</span></label>
                <input id="pn_bf_date" type="date" name="breastfeeding_date" max="{{ now()->toDateString() }}" value="{{ old('breastfeeding_date', now()->toDateString()) }}"
                       class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
            </div>
            <div>
                <label for="pn_bf_time" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Breastfeeding time <span style="color: var(--danger);">*</span></label>
                <input id="pn_bf_time" type="time" name="breastfeeding_time" value="{{ old('breastfeeding_time') }}"
                       class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Mother danger signs</label>
                <div class="grid grid-cols-2 gap-1.5 rounded-lg border p-3" style="border-color: var(--border);">
                    @foreach (\App\Models\PostnatalRecord::DANGER_SIGNS_MOTHER as $sign)
                        <label class="flex items-center gap-2 text-xs" style="color: var(--ink);">
                            <input type="checkbox" name="danger_signs_mother[]" value="{{ $sign }}" @checked(in_array($sign, old('danger_signs_mother', []), true)) class="rounded focus:ring-2" style="--tw-ring-color: var(--accent-blue);">
                            {{ $sign }}
                        </label>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Baby danger signs</label>
                <div class="grid grid-cols-2 gap-1.5 rounded-lg border p-3" style="border-color: var(--border);">
                    @foreach (\App\Models\PostnatalRecord::DANGER_SIGNS_BABY as $sign)
                        <label class="flex items-center gap-2 text-xs" style="color: var(--ink);">
                            <input type="checkbox" name="danger_signs_baby[]" value="{{ $sign }}" @checked(in_array($sign, old('danger_signs_baby', []), true)) class="rounded focus:ring-2" style="--tw-ring-color: var(--accent-blue);">
                            {{ $sign }}
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div>
                <label for="pn_vitamin_a_date" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Vitamin A</label>
                <input id="pn_vitamin_a_date" type="date" name="vitamin_a_date" max="{{ now()->toDateString() }}" value="{{ old('vitamin_a_date') }}"
                       class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
            </div>
            <div>
                <label for="pn_iron_date" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Iron (date)</label>
                <input id="pn_iron_date" type="date" name="iron_date" max="{{ now()->toDateString() }}" value="{{ old('iron_date') }}"
                       class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
            </div>
            <div>
                <label for="pn_iron_count" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Iron count</label>
                <input id="pn_iron_count" type="number" min="0" max="999" name="iron_count" value="{{ old('iron_count') }}"
                       class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
            </div>
        </div>
        <div class="rounded-lg border p-3" style="border-color: var(--border);">
            <p class="text-xs font-semibold mb-2" style="color: var(--ink-muted);">Newborn (auto-enrolled as patient)</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="pn_child_last_name" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Last name <span style="color: var(--danger);">*</span></label>
                    <input id="pn_child_last_name" type="text" name="child_last_name" value="{{ old('child_last_name', $patient->last_name) }}"
                           class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                </div>
                <div>
                    <label for="pn_child_first_name" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">First name <span style="color: var(--danger);">*</span></label>
                    <input id="pn_child_first_name" type="text" name="child_first_name" value="{{ old('child_first_name') }}"
                           class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                </div>
                <div>
                    <label for="pn_child_middle_name" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Middle name</label>
                    <input id="pn_child_middle_name" type="text" name="child_middle_name" value="{{ old('child_middle_name') }}"
                           class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                </div>
                <div>
                    <label for="pn_child_sex" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Sex <span style="color: var(--danger);">*</span></label>
                    <select id="pn_child_sex" name="child_sex" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                            style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                        <option value="M" @selected(old('child_sex') === 'M')>Male</option>
                        <option value="F" @selected(old('child_sex') === 'F')>Female</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 mt-3">
                <div>
                    <label for="pn_child_birth_weight_kg" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Birth weight (kg)</label>
                    <input id="pn_child_birth_weight_kg" type="number" step="0.01" min="0" max="20" name="child_birth_weight_kg" value="{{ old('child_birth_weight_kg') }}"
                           class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                </div>
                <div>
                    <label for="pn_child_birth_length_cm" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Birth length (cm)</label>
                    <input id="pn_child_birth_length_cm" type="number" step="0.1" min="0" max="99.9" name="child_birth_length_cm" value="{{ old('child_birth_length_cm') }}"
                           class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                </div>
            </div>
        </div>
        <div class="flex justify-end gap-2 pt-1">
            <button type="button" @click="$dispatch('close')" class="rounded-lg border px-4 py-2 text-sm font-semibold transition hover:bg-black/[0.03]"
                    style="border-color: var(--border); color: var(--ink-muted);">Cancel</button>
            <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold text-white transition hover:shadow-md" style="background: var(--primary);">Save record</button>
        </div>
    </form>
</x-modal>

@if ($current !== null)
    <x-modal name="edit-postnatal-modal" title="Edit postnatal record"
             x-on:open-edit-postnatal.window="open = true" x-on:close.window="open = false">
        <form method="POST" action="{{ route('maternal.postnatal.update', $current->id) }}" class="space-y-3">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="ep_pregnancy_outcome" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Outcome <span style="color: var(--danger);">*</span></label>
                    <select id="ep_pregnancy_outcome" name="pregnancy_outcome" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                            style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                        @foreach (\App\Models\PostnatalRecord::OUTCOMES as $value => $label)
                            <option value="{{ $value }}" @selected($current->pregnancy_outcome === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="ep_prenatal_visits_completed" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Prenatal visits</label>
                    <input id="ep_prenatal_visits_completed" type="number" min="0" max="99" name="prenatal_visits_completed" value="{{ old('prenatal_visits_completed', $current->prenatal_visits_completed) }}"
                           class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <div>
                    <label for="ep_place_delivered" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Place</label>
                    <select id="ep_place_delivered" name="place_delivered" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                            style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                        @foreach (\App\Models\PostnatalRecord::PLACES as $value => $label)
                            <option value="{{ $value }}" @selected($current->place_delivered === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="ep_mode_of_delivery" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Mode</label>
                    <select id="ep_mode_of_delivery" name="mode_of_delivery" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                            style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                        @foreach (\App\Models\PostnatalRecord::MODES as $value => $label)
                            <option value="{{ $value }}" @selected($current->mode_of_delivery === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="ep_attendant_at_birth" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Attendant</label>
                    <select id="ep_attendant_at_birth" name="attendant_at_birth" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                            style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                        @foreach (\App\Models\PostnatalRecord::ATTENDANTS as $value => $label)
                            <option value="{{ $value }}" @selected($current->attendant_at_birth === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div>
                    <label for="ep_delivery_date" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Delivery date</label>
                    <input id="ep_delivery_date" type="date" name="delivery_date" value="{{ old('delivery_date', $current->delivery_date->format('Y-m-d')) }}"
                           class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                </div>
                <div>
                    <label for="ep_delivery_time" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Time</label>
                    <input id="ep_delivery_time" type="time" name="delivery_time" value="{{ old('delivery_time', \Carbon\Carbon::parse($current->delivery_time)->format('H:i')) }}"
                           class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                </div>
                <div>
                    <label for="ep_bf_date" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Breastfeeding date</label>
                    <input id="ep_bf_date" type="date" name="breastfeeding_date" value="{{ old('breastfeeding_date', $current->breastfeeding_date->format('Y-m-d')) }}"
                           class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                </div>
                <div>
                    <label for="ep_bf_time" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Breastfeeding time</label>
                    <input id="ep_bf_time" type="time" name="breastfeeding_time" value="{{ old('breastfeeding_time', \Carbon\Carbon::parse($current->breastfeeding_time)->format('H:i')) }}"
                           class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach ([['postpartum_24h_date', '24h visit'], ['postpartum_7d_date', '7d visit'], ['postpartum_14d_date', '14d visit'], ['postpartum_28d_date', '28d visit']] as [$field, $label])
                    <div>
                        <label for="ep_{{ $field }}" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">{{ $label }}</label>
                        <input id="ep_{{ $field }}" type="date" name="{{ $field }}" value="{{ old($field, $current->{$field}?->format('Y-m-d')) }}"
                               class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                               style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                    </div>
                @endforeach
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Mother danger signs</label>
                    <div class="grid grid-cols-2 gap-1.5 rounded-lg border p-3" style="border-color: var(--border);">
                        @foreach (\App\Models\PostnatalRecord::DANGER_SIGNS_MOTHER as $sign)
                            <label class="flex items-center gap-2 text-xs" style="color: var(--ink);">
                                <input type="checkbox" name="danger_signs_mother[]" value="{{ $sign }}" @checked(in_array($sign, $current->danger_signs_mother ?? [], true)) class="rounded focus:ring-2" style="--tw-ring-color: var(--accent-blue);">
                                {{ $sign }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Baby danger signs</label>
                    <div class="grid grid-cols-2 gap-1.5 rounded-lg border p-3" style="border-color: var(--border);">
                        @foreach (\App\Models\PostnatalRecord::DANGER_SIGNS_BABY as $sign)
                            <label class="flex items-center gap-2 text-xs" style="color: var(--ink);">
                                <input type="checkbox" name="danger_signs_baby[]" value="{{ $sign }}" @checked(in_array($sign, $current->danger_signs_baby ?? [], true)) class="rounded focus:ring-2" style="--tw-ring-color: var(--accent-blue);">
                                {{ $sign }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" @click="$dispatch('close')" class="rounded-lg border px-4 py-2 text-sm font-semibold transition hover:bg-black/[0.03]"
                        style="border-color: var(--border); color: var(--ink-muted);">Cancel</button>
                <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold text-white transition hover:shadow-md" style="background: var(--primary);">Save changes</button>
            </div>
        </form>
    </x-modal>
@endif
@endsection

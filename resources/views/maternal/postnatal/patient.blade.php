@extends('layouts.app')

@section('title', 'Postnatal - '.fullName($patient->last_name, $patient->first_name, $patient->middle_name, $patient->suffix))

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
                    {{ $patient->sex }}, {{ $patient->age }} y/o &middot; Zone {{ $patient->household?->zone?->zone_number ?? $patient->household?->zone_id ?? '-' }}
                </p>
            </div>
        </div>
        <a href="{{ route('patients.show', $patient->id) }}" class="inline-flex items-center justify-center gap-1.5 rounded-lg border px-4 py-2 text-sm font-semibold transition hover:bg-black/[0.03]"
           style="border-color: var(--border); color: var(--ink-muted);">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Patient profile
        </a>
    </div>

    @if ($current !== null)
        <x-card>
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="font-display font-semibold text-lg" style="color: var(--ink);">Latest delivery</h2>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--accent-blue-soft); color: var(--accent-blue);">
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i> {{ \App\Models\PostnatalRecord::OUTCOMES[$current->pregnancy_outcome] ?? $current->pregnancy_outcome }}
                    </span>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="$dispatch('open-edit-postnatal')"
                            class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-semibold transition hover:bg-black/[0.05]"
                            style="border-color: var(--border); color: var(--accent-blue);">
                        <i class="fa-solid fa-pen" aria-hidden="true"></i> Edit
                    </button>
                    <a href="{{ route('maternal.postnatal.print', $current->id) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-semibold transition hover:bg-black/[0.05]"
                       style="border-color: var(--border); color: var(--ink-muted);">
                        <i class="fa-solid fa-print" aria-hidden="true"></i> Print
                    </a>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                <x-stat label="Delivered">{{ $current->delivery_date->format('M d, Y') }} &middot; {{ \Carbon\Carbon::parse($current->delivery_time)->format('g:i A') }}</x-stat>
                <x-stat label="Place / Mode">{{ \App\Models\PostnatalRecord::PLACES[$current->place_delivered] ?? $current->place_delivered }} &middot; {{ \App\Models\PostnatalRecord::MODES[$current->mode_of_delivery] ?? $current->mode_of_delivery }}</x-stat>
                <x-stat label="Attendant">{{ \App\Models\PostnatalRecord::ATTENDANTS[$current->attendant_at_birth] ?? $current->attendant_at_birth }}</x-stat>
                <x-stat label="Prenatal visits">{{ $current->prenatal_visits_completed ?? '-' }}</x-stat>
                <x-stat label="Breastfeeding">{{ $current->breastfeeding_date->format('M d, Y') }} &middot; {{ \Carbon\Carbon::parse($current->breastfeeding_time)->format('g:i A') }}</x-stat>
                @if ($current->pregnancy_outcome === \App\Models\PostnatalRecord::OUTCOME_LIVE_BIRTH && $current->childPatient !== null)
                    <div class="col-span-2 sm:col-span-3 lg:col-span-4 rounded-lg border p-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" style="border-color: var(--border);">
                        <div class="text-sm">
                            <p class="text-[10px] uppercase tracking-wide font-semibold" style="color: var(--ink-muted);">Newborn enrolled</p>
                            <p class="font-medium" style="color: var(--ink);">{{ fullName($current->childPatient->last_name, $current->childPatient->first_name, $current->childPatient->middle_name, $current->childPatient->suffix) }}</p>
                        </div>
                        <a href="{{ route('immunizations.patient', $current->childPatient->id) }}" class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold transition hover:bg-black/[0.05]"
                           style="border-color: var(--border); color: var(--accent-blue);">
                            <i class="fa-solid fa-syringe" aria-hidden="true"></i> Immunizations
                        </a>
                    </div>
                @endif
            </div>
            @if (! empty($current->danger_signs_mother) || ($current->pregnancy_outcome === \App\Models\PostnatalRecord::OUTCOME_LIVE_BIRTH && ! empty($current->danger_signs_baby)))
                <div class="mt-4 pt-3 border-t flex flex-wrap gap-2" style="border-color: var(--border);">
                    @if (! empty($current->danger_signs_mother))
                        <span class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold" style="background: var(--amber-soft); color: var(--amber);">
                            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Mother: {{ implode(', ', $current->danger_signs_mother) }}
                        </span>
                    @endif
                    @if ($current->pregnancy_outcome === \App\Models\PostnatalRecord::OUTCOME_LIVE_BIRTH && ! empty($current->danger_signs_baby))
                        <span class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold" style="background: var(--danger-soft); color: var(--danger);">
                            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> Baby: {{ implode(', ', $current->danger_signs_baby) }}
                        </span>
                    @endif
                </div>
            @endif
        </x-card>
    @else
        <x-card class="border-dashed p-6 text-center">
            <i class="fa-solid fa-child-reaching text-2xl mb-2" style="color: var(--ink-subtle);" aria-hidden="true"></i>
            <p class="font-semibold text-sm" style="color: var(--ink);">No postnatal record</p>
            <p class="text-xs mt-1 mb-4" style="color: var(--ink-muted);">Record the delivery to start the postpartum schedule.</p>
            <button type="button" @click="$dispatch('open-store-postnatal')"
                    class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition hover:shadow-md w-full sm:w-auto"
                    style="background: var(--primary);">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Record delivery
            </button>
        </x-card>
    @endif

    @if ($current !== null)
        @php
            $slots = [
                ['col' => 'postpartum_24h_date', 'window' => 1, 'label' => '24 hours'],
                ['col' => 'postpartum_7d_date', 'window' => 7, 'label' => '7 days'],
                ['col' => 'postpartum_14d_date', 'window' => 14, 'label' => '14 days'],
                ['col' => 'postpartum_28d_date', 'window' => 28, 'label' => '28 days'],
            ];
            $completedSlots = collect($slots)->filter(fn ($slot) => $current->{$slot['col']} !== null)->count();
        @endphp

        <x-card>
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="font-display font-semibold text-lg" style="color: var(--ink);">Postpartum schedule</h2>
                    @if ($completedSlots === count($slots))
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--teal-soft); color: var(--primary);">
                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i> All {{ $completedSlots }} visits completed
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--accent-soft); color: var(--ink-muted);">
                            <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i> {{ $completedSlots }} of {{ count($slots) }} completed
                        </span>
                    @endif
                </div>
                <p class="text-xs" style="color: var(--ink-muted);">Delivered {{ $current->delivery_date->format('M d, Y') }}</p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @foreach ($slots as $slot)
                    @php
                        $targetDate = \Carbon\Carbon::parse($current->delivery_date)->addDays($slot['window']);
                        $done = $current->{$slot['col']} !== null;
                        $overdue = ! $done && $targetDate->lt(\Carbon\Carbon::today());
                    @endphp
                    <div class="rounded-lg border p-3" style="border-color: var(--border);">
                        <p class="text-[10px] uppercase tracking-wide font-semibold" style="color: var(--ink-muted);">{{ $slot['label'] }} visit</p>
                        <p class="mt-1 text-sm font-semibold" style="color: {{ $done ? 'var(--primary)' : ($overdue ? 'var(--danger)' : 'var(--accent-blue)') }};">
                            <i class="fa-solid {{ $done ? 'fa-circle-check' : ($overdue ? 'fa-circle-exclamation' : 'fa-regular fa-calendar') }}" aria-hidden="true"></i>
                            @if ($done)
                                {{ \Carbon\Carbon::parse($current->{$slot['col']})->format('M d, Y') }}
                            @else
                                {{ $overdue ? 'Overdue' : 'Due' }} {{ $targetDate->format('M d') }}
                            @endif
                        </p>
                    </div>
                @endforeach
            </div>
        </x-card>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6">
            <div class="lg:col-span-1">
                <x-card>
                    <h2 class="font-display font-semibold text-lg mb-3" style="color: var(--ink);">Record postpartum visit</h2>
                    @if ($completedSlots === count($slots))
                        <p class="text-sm" style="color: var(--ink-subtle);">All postpartum visits for this delivery have been recorded.</p>
                    @else
                        <form method="POST" action="{{ route('maternal.postnatal.complete-visit', $current->id) }}" class="space-y-3"
                              x-data="ppSuggest('{{ $current->delivery_date->format('Y-m-d') }}')" x-init="$nextTick(suggest)">
                            @csrf
                            <div>
                                <label for="pp_slot" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Postpartum slot <span style="color: var(--danger);">*</span></label>
                                <select id="pp_slot" name="slot" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                                        style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                    @foreach ($slots as $slot)
                                        @if ($current->{$slot['col']} === null)
                                            <option value="{{ $slot['col'] }}">{{ $slot['label'] }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('slot') <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="pp_date" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Visit date <span style="color: var(--danger);">*</span></label>
                                <input id="pp_date" type="date" name="date" value="{{ old('date', now()->toDateString()) }}" max="{{ now()->toDateString() }}"
                                       class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                @error('date') <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p> @enderror
                            </div>

                            @include('maternal.partials.consultation-intake', ['fieldPrefix' => 'pp_'])

                            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition hover:shadow-md"
                                    style="background: var(--primary);">
                                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save visit
                            </button>
                        </form>
                    @endif
                </x-card>
            </div>

            <div class="lg:col-span-2 space-y-4 lg:space-y-6">
                <x-card class="overflow-hidden">
                    <h2 class="font-display font-semibold text-lg px-4 pt-4" style="color: var(--ink);">Delivery history</h2>
                    @if ($records->isEmpty())
                        <p class="px-4 py-6 text-sm" style="color: var(--ink-subtle);">No deliveries recorded for this patient.</p>
                    @else
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
                                                    -
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
                    @endif
                </x-card>
            </div>
        </div>
    @endif
</div>

<x-modal name="store-postnatal-modal" title="Record delivery"
         x-data="{{ old('intent') === 'store-postnatal' ? '{ open: true }' : '{ open: false }' }}"
         x-on:open-store-postnatal.window="open = true" x-on:close.window="open = false">
    <form method="POST" action="{{ route('maternal.postnatal.store', $patient->id) }}" class="space-y-3" x-data="{ outcome: @js(old('pregnancy_outcome', '')) }">
        @csrf
        <input type="hidden" name="intent" value="store-postnatal">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label for="pn_pregnancy_id" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Link pregnancy (optional)</label>
                <select id="pn_pregnancy_id" name="pregnancy_id" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                        style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                    <option value="">None</option>
                    @foreach ($activePregnancies as $preg)
                        <option value="{{ $preg->id }}" @selected(old('pregnancy_id') == $preg->id)>
                            LMP {{ $preg->lmp?->format('M d, Y') }} &middot; EDC {{ $preg->edc?->format('M d, Y') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="pn_pregnancy_outcome" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Outcome <span style="color: var(--danger);">*</span></label>
                <select id="pn_pregnancy_outcome" name="pregnancy_outcome" x-model="outcome" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
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
            <div x-show="outcome === 'live_birth'" x-cloak>
                <label class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Baby danger signs</label>
                <div class="grid grid-cols-2 gap-1.5 rounded-lg border p-3" style="border-color: var(--border);">
                    @foreach (\App\Models\PostnatalRecord::DANGER_SIGNS_BABY as $sign)
                        <label class="flex items-center gap-2 text-xs" style="color: var(--ink);">
                            <input type="checkbox" name="danger_signs_baby[]" value="{{ $sign }}" @checked(in_array($sign, old('danger_signs_baby', []), true)) :disabled="outcome !== 'live_birth'" class="rounded focus:ring-2" style="--tw-ring-color: var(--accent-blue);">
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
        <div x-show="outcome === 'live_birth'" x-cloak class="rounded-lg border p-3" style="border-color: var(--border);">
            <p class="text-xs font-semibold mb-2" style="color: var(--ink-muted);">Newborn (auto-enrolled as patient)</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="pn_child_last_name" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Last name <span style="color: var(--danger);">*</span></label>
                    <input id="pn_child_last_name" type="text" name="child_last_name" value="{{ old('child_last_name', $patient->last_name) }}" :disabled="outcome !== 'live_birth'"
                           class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                </div>
                <div>
                    <label for="pn_child_first_name" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">First name <span style="color: var(--danger);">*</span></label>
                    <input id="pn_child_first_name" type="text" name="child_first_name" value="{{ old('child_first_name') }}" :disabled="outcome !== 'live_birth'"
                           class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                </div>
                <div>
                    <label for="pn_child_middle_name" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Middle name</label>
                    <input id="pn_child_middle_name" type="text" name="child_middle_name" value="{{ old('child_middle_name') }}" :disabled="outcome !== 'live_birth'"
                           class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                </div>
                <div>
                    <label for="pn_child_sex" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Sex <span style="color: var(--danger);">*</span></label>
                    <select id="pn_child_sex" name="child_sex" :disabled="outcome !== 'live_birth'" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                            style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                        <option value="M" @selected(old('child_sex') === 'M')>Male</option>
                        <option value="F" @selected(old('child_sex') === 'F')>Female</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 mt-3">
                <div>
                    <label for="pn_child_birth_weight_kg" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Birth weight (kg)</label>
                    <input id="pn_child_birth_weight_kg" type="number" step="0.01" min="0" max="20" name="child_birth_weight_kg" value="{{ old('child_birth_weight_kg') }}" :disabled="outcome !== 'live_birth'"
                           class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                </div>
                <div>
                    <label for="pn_child_birth_length_cm" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Birth length (cm)</label>
                    <input id="pn_child_birth_length_cm" type="number" step="0.1" min="0" max="99.9" name="child_birth_length_cm" value="{{ old('child_birth_length_cm') }}" :disabled="outcome !== 'live_birth'"
                           class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                </div>
            </div>
        </div>
        <p x-show="outcome !== '' && outcome !== 'live_birth'" x-cloak class="text-xs font-medium rounded-lg border px-3 py-2" style="color: var(--ink-muted); border-color: var(--border);">
            Newborn details apply to live births only.
        </p>
        <div class="flex justify-end gap-2 pt-1">
            <button type="button" @click="$dispatch('close')" class="rounded-lg border px-4 py-2 text-sm font-semibold transition hover:bg-black/[0.03]"
                    style="border-color: var(--border); color: var(--ink-muted);">Cancel</button>
            <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold text-white transition hover:shadow-md" style="background: var(--primary);">Save record</button>
        </div>
    </form>
</x-modal>

@if ($current !== null)
    <x-modal name="edit-postnatal-modal" title="Edit postnatal record"
             x-data="{{ old('intent') === 'edit-postnatal' ? '{ open: true }' : '{ open: false }' }}"
             x-on:open-edit-postnatal.window="open = true" x-on:close.window="open = false">
        <form method="POST" action="{{ route('maternal.postnatal.update', $current->id) }}" class="space-y-3"
                  x-data="{ outcome: @js($current->pregnancy_outcome), deliveryDate: @js($current->delivery_date->format('Y-m-d')) }">
            @csrf
            @method('PUT')
            <input type="hidden" name="intent" value="edit-postnatal">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="ep_pregnancy_outcome" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Outcome <span style="color: var(--danger);">*</span></label>
                    <select id="ep_pregnancy_outcome" name="pregnancy_outcome" x-model="outcome" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
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
                    <input id="ep_delivery_date" type="date" name="delivery_date" max="{{ now()->toDateString() }}" value="{{ old('delivery_date', $current->delivery_date->format('Y-m-d')) }}"
                           x-model="deliveryDate" @change="deliveryDate = $event.target.value"
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
                    <input id="ep_bf_date" type="date" name="breastfeeding_date" max="{{ now()->toDateString() }}" :min="deliveryDate" value="{{ old('breastfeeding_date', $current->breastfeeding_date->format('Y-m-d')) }}"
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
                        <input id="ep_{{ $field }}" type="date" name="{{ $field }}" :min="deliveryDate" value="{{ old($field, $current->{$field}?->format('Y-m-d')) }}"
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
                <div x-show="outcome === 'live_birth'" x-cloak>
                    <label class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Baby danger signs</label>
                    <div class="grid grid-cols-2 gap-1.5 rounded-lg border p-3" style="border-color: var(--border);">
                        @foreach (\App\Models\PostnatalRecord::DANGER_SIGNS_BABY as $sign)
                        <label class="flex items-center gap-2 text-xs" style="color: var(--ink);">
                            <input type="checkbox" name="danger_signs_baby[]" value="{{ $sign }}" @checked(in_array($sign, $current->danger_signs_baby ?? [], true)) :disabled="outcome !== 'live_birth'" class="rounded focus:ring-2" style="--tw-ring-color: var(--accent-blue);">
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

<script>
    function ppSuggest(deliveryDate) {
        return {
            suggest() {
                const select = document.getElementById('pp_slot');
                if (! select) return;
                const due = new Date(deliveryDate + 'T00:00:00');
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const days = Math.round((today - due) / 86400000);
                const slot = days <= 2 ? 'postpartum_24h_date'
                    : days <= 10 ? 'postpartum_7d_date'
                    : days <= 21 ? 'postpartum_14d_date'
                    : 'postpartum_28d_date';
                if (select.querySelector('option[value="' + slot + '"]')) {
                    select.value = slot;
                }
            }
        };
    }
</script>
@endsection

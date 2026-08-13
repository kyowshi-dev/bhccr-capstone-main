@extends('layouts.app')

@section('title', 'Family Planning - '.fullName($patient->last_name, $patient->first_name, $patient->middle_name, $patient->suffix))

@section('content')
@php
    $overdue = $client !== null && $client->is_active && $client->schedule_next_visit !== null && \Carbon\Carbon::parse($client->schedule_next_visit)->lt(\Carbon\Carbon::today());
@endphp

<div class="space-y-5 lg:space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div class="flex items-start gap-3">
            <span class="mt-0.5 header-chip">
                <i class="fa-solid fa-hand-holding-heart text-lg" aria-hidden="true"></i>
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

    @if ($client !== null)
        <x-card>
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="font-display font-semibold text-lg" style="color: var(--ink);">Current client</h2>
                    @if ($client->is_active)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--teal-soft); color: var(--primary);">
                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Active
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--amber-soft); color: var(--amber);">
                            <i class="fa-solid fa-circle-user" aria-hidden="true"></i> Inactive
                        </span>
                    @endif
                </div>
                <button type="button" @click="$dispatch('open-edit-client')"
                        class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-semibold transition hover:bg-black/[0.05]"
                        style="border-color: var(--border); color: var(--accent-blue);">
                    <i class="fa-solid fa-pen" aria-hidden="true"></i> Edit
                </button>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                <x-stat label="Type">{{ \App\Models\FamilyPlanningClient::TYPES[$client->type_of_client] ?? $client->type_of_client }}</x-stat>
                <x-stat label="Method">{{ $client->method }}</x-stat>
                @if ($client->schedule_next_visit)
                    <x-stat label="Next follow-up">
                        @if ($overdue)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold" style="background: var(--danger-soft); color: var(--danger);">
                                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> Overdue {{ \Carbon\Carbon::parse($client->schedule_next_visit)->format('M d') }}
                            </span>
                        @else
                            <span style="color: var(--ink-muted);">{{ \Carbon\Carbon::parse($client->schedule_next_visit)->format('M d, Y') }}</span>
                        @endif
                    </x-stat>
                @endif
                @if ($client->drop_out_reason)
                    <div class="col-span-2 sm:col-span-3 lg:col-span-4 rounded-lg p-3 text-xs font-semibold" style="background: var(--amber-soft); color: var(--amber);">
                        <i class="fa-solid fa-triangle-exclamation mr-1" aria-hidden="true"></i> Drop-out reason: {{ $client->drop_out_reason }}
                    </div>
                @endif
            </div>
        </x-card>
    @else
        <x-card class="border-dashed p-6 text-center">
            <i class="fa-solid fa-hand-holding-heart text-2xl mb-2" style="color: var(--ink-subtle);" aria-hidden="true"></i>
            <p class="font-semibold text-sm" style="color: var(--ink);">Not an FP client</p>
            <p class="text-xs mt-1 mb-4" style="color: var(--ink-muted);">No active or past family planning record for this patient.</p>
            <button type="button" @click="$dispatch('open-register-client')"
                    class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition hover:shadow-md w-full sm:w-auto"
                    style="background: var(--primary);">
                <i class="fa-solid fa-user-plus" aria-hidden="true"></i> Register client
            </button>
        </x-card>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6">
        <div class="lg:col-span-1 space-y-4 lg:space-y-6">
            @if ($client !== null)
                <x-card>
                    <h2 class="font-display font-semibold text-lg mb-3" style="color: var(--ink);">Record follow-up visit</h2>
                    <form method="POST" action="{{ route('maternal.family-planning.visits.store', $client->id) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label for="visit_date" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Visit date <span style="color: var(--danger);">*</span></label>
                            <input id="visit_date" type="date" name="visit_date" value="{{ old('visit_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}"
                                   class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                                   style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                            @error('visit_date') <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p> @enderror
                        </div>

                        @include('maternal.partials.consultation-intake', ['fieldPrefix' => 'fp_'])

                        @include('maternal.partials.consultation-select', [
                            'fieldName' => 'consultation_id',
                            'consultations' => $consultations,
                            'selected' => old('consultation_id'),
                        ])

                        <div>
                            <label for="method" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Method <span style="color: var(--danger);">*</span></label>
                            <select id="method" name="method" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                                    style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                @foreach (\App\Models\FamilyPlanningClient::METHODS as $option)
                                    <option value="{{ $option }}" @selected(old('method', $client->method) === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            @error('method') <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="schedule_next_visit" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Next follow-up</label>
                            <input id="schedule_next_visit" type="date" name="schedule_next_visit" value="{{ old('schedule_next_visit', $client->schedule_next_visit?->format('Y-m-d')) }}"
                                   class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                                   style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                            @error('schedule_next_visit') <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition hover:shadow-md"
                                style="background: var(--primary);">
                            <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save visit
                        </button>
                    </form>
                </x-card>
            @endif
        </div>

        <div class="lg:col-span-2 space-y-4 lg:space-y-6">
            @if ($clients->count() > 1)
                <x-card class="overflow-hidden">
                    <h2 class="font-display font-semibold text-lg px-4 pt-4" style="color: var(--ink);">Past records</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left mt-2">
                            <thead class="border-b" style="background: var(--teal-soft);">
                                <tr class="text-xs uppercase tracking-wide" style="color: var(--ink-muted);">
                                    <th class="px-4 py-2.5 font-semibold whitespace-nowrap">Type</th>
                                    <th class="px-4 py-2.5 font-semibold whitespace-nowrap">Method</th>
                                    <th class="px-4 py-2.5 font-semibold whitespace-nowrap">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" style="border-color: var(--border);">
                                @foreach ($clients as $pastClient)
                                    @if ($pastClient->id === $client?->id) @continue @endif
                                    <tr class="hover:bg-black/[0.03]">
                                        <td class="px-4 py-2.5" style="color: var(--ink-muted);">{{ \App\Models\FamilyPlanningClient::TYPES[$pastClient->type_of_client] ?? $pastClient->type_of_client }}</td>
                                        <td class="px-4 py-2.5 font-medium" style="color: var(--ink);">{{ $pastClient->method }}</td>
                                        <td class="px-4 py-2.5">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold" style="background: var(--amber-soft); color: var(--amber);">
                                                {{ $pastClient->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
            @endif

            <x-card class="overflow-hidden">
                <h2 class="font-display font-semibold text-lg px-4 pt-4" style="color: var(--ink);">Visit history</h2>
                @if ($visitHistory->isEmpty())
                    <p class="px-4 py-6 text-sm" style="color: var(--ink-subtle);">No follow-up visits recorded yet.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left mt-2">
                            <thead class="border-b" style="background: var(--teal-soft);">
                                <tr class="text-xs uppercase tracking-wide" style="color: var(--ink-muted);">
                                    <th class="px-4 py-2.5 font-semibold whitespace-nowrap">Date</th>
                                    <th class="px-4 py-2.5 font-semibold whitespace-nowrap">Method</th>
                                    <th class="px-4 py-2.5 font-semibold whitespace-nowrap">Next follow-up</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" style="border-color: var(--border);">
                                @foreach ($visitHistory as $visit)
                                    <tr class="hover:bg-black/[0.03]">
                                        <td class="px-4 py-2.5 whitespace-nowrap font-medium" style="color: var(--ink);">{{ $visit->visit_date->format('M d, Y') }}</td>
                                        <td class="px-4 py-2.5" style="color: var(--ink-muted);">{{ $visit->method }}</td>
                                        <td class="px-4 py-2.5 whitespace-nowrap" style="color: var(--ink-muted);">{{ $visit->schedule_next_visit?->format('M d, Y') ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>

            @if ($client !== null)
                <div class="flex justify-end">
                    <a href="{{ route('maternal.family-planning.print', $client->id) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 rounded-lg border px-4 py-2 text-sm font-semibold transition hover:bg-black/[0.03]"
                       style="border-color: var(--border); color: var(--ink-muted);">
                        <i class="fa-solid fa-print" aria-hidden="true"></i> Print client card
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<x-modal name="register-client-modal" title="Register family planning client"
         x-on:open-register-client.window="open = true" x-on:close.window="open = false">
    <form method="POST" action="{{ route('maternal.family-planning.store', $patient->id) }}" class="space-y-3">
        @csrf
        <div>
            <label for="type_of_client" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Type of client <span style="color: var(--danger);">*</span></label>
            <select id="type_of_client" name="type_of_client" x-data x-ref="typeSelect" @change="$refs.reasonWrap.classList.toggle('hidden', $event.target.value !== 'drop_out')"
                    class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                    style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                @foreach (\App\Models\FamilyPlanningClient::TYPES as $value => $label)
                    <option value="{{ $value }}" @selected(old('type_of_client') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('type_of_client') <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="fp_method" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Method <span style="color: var(--danger);">*</span></label>
            <select id="fp_method" name="method" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                    style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                @foreach (\App\Models\FamilyPlanningClient::METHODS as $option)
                    <option value="{{ $option }}" @selected(old('method') === $option)>{{ $option }}</option>
                @endforeach
            </select>
            @error('method') <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p> @enderror
        </div>
        <div x-data="{ hidden: {{ old('type_of_client') === 'drop_out' ? 'false' : 'true' }} }" x-ref="reasonWrap" :class="hidden ? 'hidden' : ''">
            <label for="drop_out_reason" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Drop-out reason</label>
            <textarea id="drop_out_reason" name="drop_out_reason" rows="2"
                      class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                      style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">{{ old('drop_out_reason') }}</textarea>
            @error('drop_out_reason') <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="schedule_next_visit" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Next follow-up</label>
            <input id="schedule_next_visit" type="date" name="schedule_next_visit" value="{{ old('schedule_next_visit') }}"
                   class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                   style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
            @error('schedule_next_visit') <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p> @enderror
        </div>
        <div class="flex justify-end gap-2 pt-1">
            <button type="button" @click="$dispatch('close')" class="rounded-lg border px-4 py-2 text-sm font-semibold transition hover:bg-black/[0.03]"
                    style="border-color: var(--border); color: var(--ink-muted);">Cancel</button>
            <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold text-white transition hover:shadow-md" style="background: var(--primary);">Register</button>
        </div>
    </form>
</x-modal>

@if ($client !== null)
    <x-modal name="edit-client-modal" title="Edit client record"
         x-on:open-edit-client.window="open = true" x-on:close.window="open = false">
        <form method="POST" action="{{ route('maternal.family-planning.update', $client->id) }}" class="space-y-3">
            @csrf
            @method('PUT')
            <div>
                <label for="edit_type_of_client" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Type of client <span style="color: var(--danger);">*</span></label>
                <select id="edit_type_of_client" name="type_of_client" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                        style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                    @foreach (\App\Models\FamilyPlanningClient::TYPES as $value => $label)
                        <option value="{{ $value }}" @selected($client->type_of_client === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('type_of_client') <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="edit_method" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Method <span style="color: var(--danger);">*</span></label>
                <select id="edit_method" name="method" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                        style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                    @foreach (\App\Models\FamilyPlanningClient::METHODS as $option)
                        <option value="{{ $option }}" @selected($client->method === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                @error('method') <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="edit_drop_out_reason" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Drop-out reason</label>
                <textarea id="edit_drop_out_reason" name="drop_out_reason" rows="2"
                          class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                          style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">{{ old('drop_out_reason', $client->drop_out_reason) }}</textarea>
                @error('drop_out_reason') <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="edit_schedule_next_visit" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Next follow-up</label>
                <input id="edit_schedule_next_visit" type="date" name="schedule_next_visit" value="{{ old('schedule_next_visit', $client->schedule_next_visit?->format('Y-m-d')) }}"
                       class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                @error('schedule_next_visit') <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="edit_is_active" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Status</label>
                <select id="edit_is_active" name="is_active" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                        style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                    <option value="1" @selected($client->is_active)>Active</option>
                    <option value="0" @selected(! $client->is_active)>Inactive</option>
                </select>
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

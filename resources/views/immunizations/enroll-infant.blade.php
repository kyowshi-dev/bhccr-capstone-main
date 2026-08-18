@extends('layouts.app')

@section('title', 'Enroll infant')

@section('content')
@php
    $enrollHasErrors = $errors->hasAny([
        'first_name', 'middle_name', 'last_name', 'sex',
        'date_of_birth', 'birth_weight', 'mother_name', 'mother_id', 'father_name',
        'household_id', 'create_household', 'zone_id', 'family_name_head', 'contact_number', 'duplicate',
    ]);
    $duplicateMsg = $enrollHasErrors ? $errors->first('duplicate') : null;
@endphp

<div class="space-y-5 lg:space-y-6" x-data='infantEnroll()'>
    <div>
        <a href="{{ route('immunizations.index', ['mode' => 'child']) }}" class="text-sm font-medium hover:underline mb-1 inline-block" style="color: var(--primary);">Back to immunization queue</a>
        <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Enroll infant</h1>
        <p class="text-sm mt-1" style="color: var(--ink-muted);">Quick-add an infant and attach them to a household.</p>
    </div>

    <div class="rounded-xl border overflow-hidden max-w-2xl" style="background: var(--bg-surface-elevated); border-color: var(--border);">
        <form action="{{ route('immunizations.enroll-infant') }}" method="POST">
            @csrf
            <div class="p-5 space-y-4">
                @if ($duplicateMsg)
                    <div class="rounded-xl border px-4 py-3 text-sm" style="background: var(--accent-soft); border-color: var(--amber); color: var(--amber);">
                        <i class="fa-solid fa-circle-exclamation mr-1.5" aria-hidden="true"></i>{{ $duplicateMsg }}
                    </div>
                @endif

                <fieldset class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <legend class="sr-only">Infant identity</legend>

                    <div class="sm:col-span-2">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="enroll_first_name" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">
                                    First name <span style="color: var(--danger);">*</span>
                                </label>
                                <input x-ref="firstName" id="enroll_first_name" name="first_name" type="text" required minlength="2" maxlength="50"
                                       value="{{ old('first_name') }}" autocomplete="off"
                                       class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                @error('first_name')
                                    <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="enroll_last_name" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">
                                    Last name / family name <span style="color: var(--danger);">*</span>
                                </label>
                                <input id="enroll_last_name" name="last_name" type="text" required minlength="2" maxlength="50"
                                       value="{{ old('last_name') }}"
                                       x-model="surname"
                                       @input.debounce.300ms="searchMatches()"
                                       autocomplete="off"
                                       class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                @error('last_name')
                                    <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="enroll_middle_name" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Middle name</label>
                        <input id="enroll_middle_name" name="middle_name" type="text" maxlength="50" value="{{ old('middle_name') }}"
                               class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                               style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                        @error('middle_name')
                            <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="enroll_sex" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">
                            Sex <span style="color: var(--danger);">*</span>
                        </label>
                        <select id="enroll_sex" name="sex" required
                                class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                                style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                            <option value="Male" @selected(old('sex') === 'Male')>Male</option>
                            <option value="Female" @selected(old('sex') === 'Female')>Female</option>
                        </select>
                        @error('sex')
                            <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="enroll_dob" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">
                            Date of birth <span style="color: var(--danger);">*</span>
                        </label>
                        <input id="enroll_dob" name="date_of_birth" type="date" required max="{{ now()->toDateString() }}"
                               value="{{ old('date_of_birth') }}"
                               class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                               style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                        @error('date_of_birth')
                            <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="enroll_birth_weight" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">
                            Birth weight (kg) <span style="color: var(--danger);">*</span>
                        </label>
                        <input id="enroll_birth_weight" name="birth_weight" type="number" step="0.01" min="0.1" max="10" required
                               value="{{ old('birth_weight') }}" placeholder="e.g. 3.20"
                               class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                               style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                        @error('birth_weight')
                            <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="enroll_mother" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">
                                    Mother's full name <span style="color: var(--danger);">*</span>
                                </label>
                                <input id="enroll_mother" name="mother_name" type="text" required minlength="2" maxlength="255"
                                       value="{{ old('mother_name') }}"
                                       x-model="motherQuery"
                                       @input.debounce.300ms="searchMothers()"
                                       autocomplete="off"
                                       placeholder="Search existing patient or type the full name"
                                       class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                <input type="hidden" name="mother_id" :value="motherSelected ? motherSelected.id : ''">

                                <div x-show="motherSelected" x-cloak class="mt-2 mb-2 rounded-xl border px-4 py-3 flex items-center justify-between gap-3" style="border-color: var(--primary); background: var(--teal-soft);">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <i class="fa-solid fa-user text-sm" style="color: var(--primary);" aria-hidden="true"></i>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium truncate" style="color: var(--ink);" x-text="motherSelected?.text"></p>
                                            <p class="text-xs" style="color: var(--ink-muted);" x-text="motherSelected?.subtext"></p>
                                        </div>
                                    </div>
                                    <button type="button" @click="unlinkMother()" class="shrink-0 text-xs font-semibold hover:underline" style="color: var(--primary);">Remove</button>
                                </div>

                                <div x-show="!motherSelected && motherQuery.trim().length >= 2" class="mt-2 space-y-2">
                                    <p class="text-xs font-medium" style="color: var(--ink-muted);" x-show="searchingMothers">
                                        <i class="fa-solid fa-spinner fa-spin mr-1" aria-hidden="true"></i>Searching patients…
                                    </p>

                                    <template x-for="mother in motherMatches" :key="mother.id">
                                        <button type="button" @click="linkMother(mother)"
                                                class="w-full flex items-center justify-between gap-3 rounded-xl border px-4 py-3 text-left transition-colors hover:bg-black/[0.03]"
                                                style="border-color: var(--border); background: var(--bg-surface);">
                                            <span class="flex items-center gap-2.5 min-w-0">
                                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg" style="background: var(--teal-soft); color: var(--primary);">
                                                    <i class="fa-solid fa-user text-xs" aria-hidden="true"></i>
                                                </span>
                                                <span class="min-w-0">
                                                    <span class="block text-sm font-medium truncate" style="color: var(--ink);" x-text="mother.text"></span>
                                                    <span class="block text-xs" style="color: var(--ink-muted);" x-text="mother.subtext"></span>
                                                </span>
                                            </span>
                                            <span class="shrink-0 text-xs font-semibold" style="color: var(--primary);">Link</span>
                                        </button>
                                    </template>

                                    <p x-show="!searchingMothers && motherMatches.length === 0" class="rounded-xl border border-dashed px-4 py-3 text-xs" style="border-color: var(--border); color: var(--ink-muted);">
                                        No matching patient. The typed name will be saved as the mother's name.
                                    </p>
                                </div>

                                @error('mother_name')
                                    <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p>
                                @enderror
                                @error('mother_id')
                                    <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="enroll_father" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Father's full name</label>
                                <input id="enroll_father" name="father_name" type="text" maxlength="255" value="{{ old('father_name') }}"
                                       autocomplete="off"
                                       class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                @error('father_name')
                                    <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </fieldset>

                <div class="border-t pt-5" style="border-color: var(--border);">
                    <div class="mb-3">
                        <p class="text-xs font-medium mb-1" style="color: var(--ink-muted);">Household</p>
                        <p class="text-xs" style="color: var(--ink-subtle);">We match households by family name. Attach to an existing one or create a new household.</p>
                    </div>

                    <input type="hidden" name="household_id" :value="selected ? selected.id : ''">
                    <input type="hidden" name="create_household" :value="createHousehold ? 1 : 0">

                    <div x-show="!createHousehold">
                        <p class="text-xs font-medium mb-2" style="color: var(--ink-muted);" x-text="surname.trim().length >= 2 ? `Matches for “${surname}”` : 'Type the family name to search existing households.'"></p>

                        <div x-show="selected" class="mb-3 rounded-xl border px-4 py-3 flex items-center justify-between gap-3" style="border-color: var(--primary); background: var(--teal-soft);">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <i class="fa-solid fa-house-chimney text-sm" style="color: var(--primary);" aria-hidden="true"></i>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium truncate" style="color: var(--ink);" x-text="selected?.family_name_head"></p>
                                    <p class="text-xs" style="color: var(--ink-muted);" x-text="`${selected?.zone?.zone_number ?? 'Zone'} · ${selected?.patients_count ?? 0} member(s)`"></p>
                                </div>
                            </div>
                            <button type="button" @click="selected = null" class="shrink-0 text-xs font-semibold hover:underline" style="color: var(--primary);">Remove</button>
                        </div>

                        <div x-show="!selected && surname.trim().length >= 2" class="space-y-2">
                            <p class="text-xs font-medium" style="color: var(--ink-muted);" x-show="searching">
                                <i class="fa-solid fa-spinner fa-spin mr-1" aria-hidden="true"></i>Searching…
                            </p>

                            <template x-for="household in matches" :key="household.id">
                                <div class="flex items-center justify-between gap-3 rounded-xl border px-4 py-3 transition-colors hover:bg-black/[0.03]" style="border-color: var(--border); background: var(--bg-surface);">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg" style="background: var(--teal-soft); color: var(--primary);">
                                            <i class="fa-solid fa-house-chimney text-xs" aria-hidden="true"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium truncate" style="color: var(--ink);" x-text="household.family_name_head"></p>
                                            <p class="text-xs" style="color: var(--ink-muted);" x-text="`${household.zone?.zone_number ?? 'Zone'} · ${household.patients_count ?? 0} member(s)`"></p>
                                        </div>
                                    </div>
                                    <button type="button"
                                            @click="attach(household)"
                                            class="shrink-0 inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-white transition hover:opacity-90"
                                            style="background: var(--primary);">
                                        <i class="fa-solid fa-plus" aria-hidden="true"></i> Attach
                                    </button>
                                </div>
                            </template>

                            <p x-show="!searching && matches.length === 0" class="rounded-xl border border-dashed px-4 py-3 text-xs" style="border-color: var(--border); color: var(--ink-muted);">
                                No matching household. <button type="button" @click="toggleCreate()" class="font-semibold hover:underline" style="color: var(--primary);">Create a new household</button> instead.
                            </p>
                        </div>

                        @error('household_id')
                            <p class="mt-2 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-show="createHousehold" class="space-y-4">
                        <div class="flex items-center justify-between rounded-xl border px-4 py-3" style="border-color: var(--border); background: var(--teal-soft);">
                            <div>
                                <p class="text-sm font-medium" style="color: var(--ink);">Creating a new household</p>
                                <p class="text-xs mt-0.5" style="color: var(--ink-muted);">A new household record will be created and this infant attached.</p>
                            </div>
                            <button type="button" @click="toggleCreate()" class="shrink-0 text-xs font-semibold hover:underline" style="color: var(--primary);">Back to search</button>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="enroll_zone" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">
                                    Purok (zone) <span style="color: var(--danger);">*</span>
                                </label>
                                <select id="enroll_zone" name="zone_id" :disabled="!createHousehold" required
                                        class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                                        style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                    <option value="">Select purok</option>
                                    @foreach ($zones as $zone)
                                        <option value="{{ $zone->id }}" @selected(old('zone_id') == $zone->id)>{{ $zone->zone_number }}</option>
                                    @endforeach
                                </select>
                                @error('zone_id')
                                    <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="enroll_family_head" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">
                                    Family name (head) <span style="color: var(--danger);">*</span>
                                </label>
                                <input id="enroll_family_head" name="family_name_head" type="text" required maxlength="255"
                                       x-model="familyHead"
                                       class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                @error('family_name_head')
                                    <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="enroll_contact" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Contact number</label>
                                <input id="enroll_contact" name="contact_number" type="tel" maxlength="32" value="{{ old('contact_number') }}"
                                       class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                @error('contact_number')
                                    <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2 border-t px-5 py-4" style="border-color: var(--border);">
                <a href="{{ route('immunizations.index', ['mode' => 'child']) }}" class="rounded-lg border px-4 py-2.5 text-sm font-semibold transition-colors hover:bg-black/[0.03]" style="border-color: var(--border); color: var(--ink-muted);">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90" style="background: var(--primary);">
                    <i class="fa-solid fa-user-plus" aria-hidden="true"></i> Enroll infant
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function infantEnroll() {
        return {
            surname: @js(old('last_name', '')),
            familyHead: @js(old('family_name_head', '')),
            matches: [],
            searching: false,
            selected: null,
            createHousehold: @js((bool) old('create_household')),
            motherQuery: @js(old('mother_name', '')),
            motherMatches: [],
            searchingMothers: false,
            motherSelected: null,
            init() {
                if (this.createHousehold && ! this.familyHead) {
                    this.familyHead = this.surname;
                }
            },
            async searchMatches() {
                if (this.surname.trim().length < 2) { this.matches = []; return; }
                this.searching = true;
                try {
                    const url = @json(route('immunizations.household-match'))
                        + '?surname=' + encodeURIComponent(this.surname.trim());
                    const response = await safeFetch(url);
                    const data = response.ok ? await response.json() : [];
                    this.matches = Array.isArray(data) ? data : [];
                } catch (e) {
                    this.matches = [];
                }
                this.searching = false;
            },
            attach(household) {
                this.selected = household;
                this.familyHead = household.family_name_head;
            },
            toggleCreate() {
                this.createHousehold = !this.createHousehold;
                if (this.createHousehold && ! this.familyHead) {
                    this.familyHead = this.surname;
                }
            },
            async searchMothers() {
                if (this.motherSelected && this.motherQuery.trim() !== this.motherSelected.text) {
                    this.unlinkMother();
                }

                if (this.motherQuery.trim().length < 2) { this.motherMatches = []; return; }
                this.searchingMothers = true;
                try {
                    const url = @json(route('immunizations.mother-match'))
                        + '?query=' + encodeURIComponent(this.motherQuery.trim());
                    const response = await safeFetch(url);
                    const data = response.ok ? await response.json() : [];
                    this.motherMatches = Array.isArray(data) ? data : [];
                } catch (e) {
                    this.motherMatches = [];
                }
                this.searchingMothers = false;
            },
            linkMother(mother) {
                this.motherSelected = mother;
                this.motherQuery = mother.text;
                this.motherMatches = [];
            },
            unlinkMother() {
                this.motherSelected = null;
                this.motherMatches = [];
            },
        };
    }
</script>
@endsection

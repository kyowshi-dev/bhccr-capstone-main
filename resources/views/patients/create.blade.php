@extends('layouts.app')

@section('title', 'Enroll Patient')

@section('content')
<div class="max-w-4xl mx-auto space-y-2 lg:space-y-3">
    @if ($errors->any())
        <div class="rounded-xl bg-danger-soft border border-danger/30 px-3 lg:px-4 py-2 lg:py-3 text-xs lg:text-sm text-danger">
            <p class="font-semibold mb-1">Please fix the following:</p>
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('patients.store') }}" method="POST" class="mt-0 bg-surface rounded-xl lg:rounded-lg shadow-sm border-border space-y-3 lg:space-y-4" x-data='patientEnroll()'>
        @csrf

        <div class="pb-3 lg:pb-4 border-b border-border">
            <h3 class="text-sm lg:text-base font-extrabold mb-2 lg:mb-3 flex items-center" style="color: var(--ink);">
                <span class="mr-2"><i class="fas fa-user"></i></span>
                Personal Information
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 lg:gap-4 mb-2 lg:mb-3">
                <div>
                    <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" 
                           class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('first_name') border-danger bg-danger-soft @else border-border @enderror focus:ring-accent-blue focus:border-accent-blue text-sm">
                    @error('first_name') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Middle Name</label>
                    <input type="text" name="middle_name" value="{{ old('middle_name') }}"
                           class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('middle_name') border-danger bg-danger-soft @else border-border @enderror focus:ring-accent-blue focus:border-accent-blue text-sm">
                    @error('middle_name') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}"
                           x-model="surname"
                           @input.debounce.300ms="searchSurnameMatches()"
                           autocomplete="off"
                           class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('last_name') border-danger bg-danger-soft @else border-border @enderror focus:ring-accent-blue focus:border-accent-blue text-sm">
                    @error('last_name') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror

                    <div x-show="!creating && surname.trim().length >= 2" x-cloak class="mt-2 space-y-2">
                        <div x-show="surnameSelected" class="rounded-xl border px-4 py-3 flex items-center justify-between gap-3" style="border-color: var(--primary); background: var(--teal-soft);">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <i class="fa-solid fa-house-chimney text-sm" style="color: var(--primary);" aria-hidden="true"></i>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium truncate" style="color: var(--ink);" x-text="surnameSelected?.text"></p>
                                    <p class="text-xs" style="color: var(--ink-muted);" x-text="surnameSelected?.subtext"></p>
                                </div>
                            </div>
                            <button type="button" @click="detachSurname()" class="shrink-0 text-xs font-semibold hover:underline" style="color: var(--primary);">Remove</button>
                        </div>

                        <template x-if="!surnameSelected">
                            <div>
                                <p class="text-xs font-medium mb-2" style="color: var(--ink-muted);" x-text="surnameSearching ? `Searching households for “${surname.trim()}”…` : 'Matching households by family name'"></p>

                                <div class="space-y-2">
                                    <template x-for="household in surnameMatches" :key="household.id">
                                        <div class="flex items-center justify-between gap-3 rounded-xl border px-4 py-3 transition-colors hover:bg-black/[0.03]" style="border-color: var(--border); background: var(--bg-surface);">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg" style="background: var(--teal-soft); color: var(--primary);">
                                                    <i class="fa-solid fa-house-chimney text-xs" aria-hidden="true"></i>
                                                </span>
                                                <div class="min-w-0">
                                                    <p class="text-sm font-medium truncate" style="color: var(--ink);" x-text="household.text"></p>
                                                    <p class="text-xs" style="color: var(--ink-muted);" x-text="household.subtext"></p>
                                                </div>
                                            </div>
                                            <button type="button"
                                                    @click="attachSurname(household)"
                                                    class="shrink-0 inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-white transition hover:opacity-90"
                                                    style="background: var(--primary);">
                                                <i class="fa-solid fa-plus" aria-hidden="true"></i> Attach
                                            </button>
                                        </div>
                                    </template>

                                    <p x-show="!surnameSearching && surnameMatches.length === 0" class="rounded-xl border border-dashed px-4 py-3 text-xs" style="border-color: var(--border); color: var(--ink-muted);">
                                        No matching household. Search manually below or create a new household.
                                    </p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 lg:gap-4 mb-2 lg:mb-3">
                <div>
                    <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Mother's Name <span class="text-danger">*</span></label>
                    <input type="text" name="mother_name" value="{{ old('mother_name') }}"
                           class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('mother_name') border-danger bg-danger-soft @else border-border @enderror focus:ring-accent-blue focus:border-accent-blue text-sm"
                           placeholder="e.g. Maria Santos">
                    @error('mother_name') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Partner / Spouse's Name <span class="text-danger">*</span></label>
                    <input type="text" name="spouse_name" value="{{ old('spouse_name') }}"
                           class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('spouse_name') border-danger bg-danger-soft @else border-border @enderror focus:ring-accent-blue focus:border-accent-blue text-sm"
                           placeholder="">
                    @error('spouse_name') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Family Member (Posisyon sa Pamilya) <span class="text-danger">*</span></label>
                    <select name="family_relationship" class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('family_relationship') border-danger @else border-border @enderror focus:ring-accent-blue focus:border-accent-blue text-sm">
                        <option value="">Select relationship</option>
                        @foreach(\App\Models\Patient::FAMILY_RELATIONSHIP_OPTIONS as $relationship)
                            <option value="{{ $relationship }}" {{ old('family_relationship') === $relationship ? 'selected' : '' }}>{{ $relationship }}</option>
                        @endforeach
                    </select>
                    @error('family_relationship') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 mb-2 lg:mb-3">
                <div>
                    <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Suffix</label>
                    <input type="text" name="suffix" placeholder="Jr, III" value="{{ old('suffix') }}" class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border focus:ring-accent-blue focus:border-accent-blue text-sm">
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Sex <span class="text-danger">*</span></label>
                    <select name="sex" class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('sex') border-danger @else border-border @enderror focus:ring-accent-blue focus:border-accent-blue text-sm">
                        <option value="Male" {{ old('sex') == 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ old('sex') == 'Female' ? 'selected' : '' }}>Female</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Birthdate <span class="text-danger">*</span></label>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}"
                           class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('date_of_birth') border-danger bg-danger-soft @else border-border @enderror focus:ring-accent-blue focus:border-accent-blue text-sm">
                    @error('date_of_birth') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Blood Type (optional)</label>
                    <select name="blood_type" class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border focus:ring-accent-blue focus:border-accent-blue text-sm">
                        <option value="" {{ old('blood_type') === '' || old('blood_type') === null ? 'selected' : '' }}></option>
                        @foreach(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $type)
                            <option value="{{ $type }}" {{ old('blood_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Birth Place</label>
                <input type="text" name="birth_place" value="{{ old('birth_place') }}" 
                       placeholder="City/Municipality, Province" class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border focus:ring-accent-blue focus:border-accent-blue text-sm">
            </div>
        </div>

        <div class="pb-3 lg:pb-4 border-b border-border">
            <h3 class="text-sm lg:text-base font-extrabold mb-2 lg:mb-3 flex items-center" style="color: var(--ink);">
                <span class="mr-2"><i class="fas fa-peso-sign"></i></span>
                Socio-Economic Status
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 lg:gap-4 mb-2 lg:mb-3">
                <div>
                    <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Civil Status <span class="text-danger">*</span></label>
                    <select name="civil_status" class="w-full px-3 lg:px-4 py-2 rounded-xl border focus:ring-accent-blue focus:border-accent-blue text-sm">
                        @foreach(['Single (Walang Asawa)', 'Married (May Asawa)', 'Annulled (Hiwalay)', 'Widow/er (Balo)', 'Separated (Hiwalay)', 'Co-Habitation (Paninirahang magkasama)'] as $status)
                            <option value="{{ $status }}" {{ old('civil_status') == $status ? 'selected' : '' }}>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Educational Attainment</label>
                    <select name="educational_attainment" class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border focus:ring-accent-blue focus:border-accent-blue text-sm">
                        <option value="">Select Level</option>
                        @foreach(['No Formal Education', 'Elementary', 'High School', 'College', 'Postgraduate', 'Vocational'] as $edu)
                            <option value="{{ $edu }}" {{ old('educational_attainment') == $edu ? 'selected' : '' }}>{{ $edu }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs uppercase font-bold mb-1">Employment Status</label>
                    <select name="employment_status" class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border focus:ring-accent-blue focus:border-accent-blue text-sm">
                        <option value="">Select employment status</option>
                        <option value="Student" {{ old('employment_status') === 'Student' ? 'selected' : '' }}>Student (Estudyante)</option>
                        <option value="Employed" {{ old('employment_status') === 'Employed' ? 'selected' : '' }}>Employed (May trabaho)</option>
                        <option value="Unknown" {{ old('employment_status') === 'Unknown' ? 'selected' : '' }}>Unknown (Hindi malaman)</option>
                        <option value="Student" {{ old('employment_status') === 'Student' ? 'selected' : '' }}>Retired (Retirado)</option>
                        <option value="Retired" {{ old('employment_status') === 'Retired' ? 'selected' : '' }}>None/Unemployed (Walang Trabaho)</option>
                        <option value="Others" {{ old('employment_status') === 'Others' ? 'selected' : '' }}>Others</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:gap-4 mb-4">
                <div>
                    <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">PhilHealth Member?</label>
                    <div class="flex items-center gap-4">
                        <label class="inline-flex items-center gap-2 text-sm -700">
                            <input type="radio" name="is_philhealth_member" value="y" x-model="isPhilhealthMember" {{ old('is_philhealth_member') === 'y' ? 'checked' : '' }} class="h-4 w-4 text-accent-blue border-border focus:ring-accent-blue">
                            <span>Yes</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm -700">
                            <input type="radio" name="is_philhealth_member" value="n" x-model="isPhilhealthMember" {{ old('is_philhealth_member', 'n') === 'n' ? 'checked' : '' }} class="h-4 w-4 text-accent-blue border-border focus:ring-accent-blue">
                            <span>No</span>
                        </label>
                    </div>
                    @error('is_philhealth_member') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">PhilHealth Number <span class="text-ink-subtle font-normal normal-case">(optional)</span></label>
                    <input type="text" name="philhealth_no" value="{{ old('philhealth_no') }}"
                           placeholder="12-123456789-0"
                           :disabled="isPhilhealthMember !== 'y'"
                           x-bind:class="isPhilhealthMember !== 'y' ? 'opacity-50 bg-teal-soft cursor-not-allowed' : ''"
                           class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('philhealth_no') border-danger bg-danger-soft @else border-border @enderror focus:ring-accent-blue focus:border-accent-blue text-sm">
                    @error('philhealth_no') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:gap-4 mb-3">
                <div>
                    <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Membership Category <span class="text-ink-subtle font-normal normal-case">(optional)</span></label>
                    <select name="membership_category"
                            :disabled="isPhilhealthMember !== 'y'"
                            x-bind:class="isPhilhealthMember !== 'y' ? 'opacity-50 cursor-not-allowed' : ''"
                            class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('membership_category') border-danger @else border-border @enderror focus:ring-accent-blue focus:border-accent-blue text-sm">
                        <option value="">Select category</option>
                        @foreach(\App\Models\Patient::PHILHEALTH_MEMBERSHIP_CATEGORIES as $category)
                            <option value="{{ $category }}" {{ old('membership_category') === $category ? 'selected' : '' }}>{{ $category }}</option>
                        @endforeach
                    </select>
                    @error('membership_category') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">PhilHealth Status <span class="text-ink-subtle font-normal normal-case">(optional)</span></label>
                    <select name="status_type"
                            :disabled="isPhilhealthMember !== 'y'"
                            x-bind:class="isPhilhealthMember !== 'y' ? 'opacity-50 cursor-not-allowed' : ''"
                            class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('status_type') border-danger @else border-border @enderror focus:ring-accent-blue focus:border-accent-blue text-sm">
                        <option value="">Select status</option>
                        @foreach(\App\Models\Patient::PHILHEALTH_STATUS_TYPES as $status)
                            <option value="{{ $status }}" {{ old('status_type') === $status ? 'selected' : '' }}>{{ $status }}</option>
                        @endforeach
                    </select>
                    @error('status_type') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:gap-4 mb-4">
                <div>
                    <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">PCB Member</label>
                    <div class="flex items-center gap-4">
                        <label class="inline-flex items-center gap-2 text-sm -700">
                            <input type="radio" name="is_pcb_member" value="y" {{ old('is_pcb_member') === 'y' ? 'checked' : '' }} class="h-4 w-4 text-accent-blue border-border focus:ring-accent-blue">
                            <span>Yes</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm -700">
                            <input type="radio" name="is_pcb_member" value="n" {{ old('is_pcb_member', 'n') === 'n' ? 'checked' : '' }} class="h-4 w-4 text-accent-blue border-border focus:ring-accent-blue">
                            <span>No</span>
                        </label>
                    </div>
                    @error('is_pcb_member') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex flex-wrap gap-4 lg:gap-6 p-2 lg:p-3 bg-teal-soft rounded-xl border border-border">
                <div class="flex items-center">
                    <input type="checkbox" name="has_4ps" id="4ps" value="1" 
                           class="h-4 w-4 text-accent-blue focus:ring-accent-blue border-border rounded"
                           {{ old('has_4ps') ? 'checked' : '' }}>
                    <label for="4ps" class="ml-2 block text-xs lg:text-sm -900 font-medium">4Ps Member</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="has_nhts" id="nhts" value="1"
                           class="h-4 w-4 text-accent-blue focus:ring-accent-blue border-border rounded"
                           {{ old('has_nhts') ? 'checked' : '' }}>
                    <label for="nhts" class="ml-2 block text-xs lg:text-sm -900 font-medium">NHTS / Indigent</label>
                </div>
            </div>
        </div>

        <div class="pb-2 lg:pb-3 border-b border-border">
            <h3 class="text-sm lg:text-base font-extrabold mb-2 lg:mb-3 flex items-center" style="color: var(--ink);">
                <span class="mr-2"><i class="fas fa-home"></i></span>
                Household Information
            </h3>

            <div class="space-y-4">
                <input type="hidden" name="create_new_household" :value="creating ? 1 : 0">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <button type="button"
                            @click="creating = false"
                            :class="creating ? 'border-border bg-teal-soft text-ink-muted' : 'border-primary bg-surface shadow-sm'"
                            class="rounded-2xl border p-4 text-left transition-all duration-200 hover:border-primary">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold">Select Existing Household</p>
                                <p class="text-xs text-ink-muted mt-1">Search by family name, zone, or contact.</p>
                            </div>
                            <span x-show="!creating" x-cloak class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold" style="background: var(--teal-soft); color: var(--primary);">Active</span>
                        </div>
                    </button>

                    <button type="button"
                            @click="enableCreating()"
                            :class="creating ? 'border-primary bg-surface shadow-sm' : 'border-border bg-teal-soft text-ink-muted'"
                            class="rounded-2xl border p-4 text-left transition-all duration-200 hover:border-primary">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold">Create New Household</p>
                                <p class="text-xs text-ink-muted mt-1">Add a new household record and attach this patient.</p>
                            </div>
                            <span x-show="creating" x-cloak class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold" style="background: var(--teal-soft); color: var(--primary);">Active</span>
                        </div>
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    <div x-show="!creating" x-cloak class="bg-teal-soft border border-border rounded-2xl p-4">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs lg:text-sm font-medium text-ink mb-1">
                                    Select Household <span class="text-danger">*</span>
                                </label>
                                <p class="text-xs text-ink-muted">Search by family name, zone, or contact.</p>
                            </div>

                            <div class="flex items-center gap-3 flex-wrap mb-3">
                                <input type="checkbox"
                                       id="transient_unmapped"
                                       x-model="isTransient"
                                       @change="onTransientToggle()"
                                       class="h-4 w-4 text-accent-blue focus:ring-accent-blue border-border rounded"
                                       :disabled="transientHouseholdId === null">
                                <label for="transient_unmapped" class="text-xs lg:text-sm font-medium text-ink">
                                    Transient/Unmapped
                                </label>

                                <template x-if="transientHouseholdId === null">
                                    <span class="text-xs text-danger">Transient household not found.</span>
                                </template>
                            </div>

                            <input type="hidden" name="household_id" x-model="householdId">

                            <div class="relative">
                                <input type="text"
                                       x-ref="householdSearch"
                                       x-model="query"
                                       @input.debounce.250ms="search()"
                                       @focus="dropdownOpen = true"
                                       @keydown.escape="dropdownOpen = false"
                                       @click="dropdownOpen = true"
                                       :disabled="isTransient"
                                       placeholder="Search household (family name / zone / contact)..."
                                       class="w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-xl border @error('household_id') border-danger bg-danger-soft @else border-border @enderror focus:ring-accent-blue focus:border-accent-blue text-sm"
                                       autocomplete="off">

                                <div x-show="dropdownOpen && !isTransient && autocompleteResults.length > 0" class="absolute z-20 w-full bg-surface mt-1 border border-border rounded-xl shadow-lg max-h-60 overflow-y-auto">
                                    <ul>
                                        <template x-for="item in autocompleteResults" :key="item.id">
                                            <li class="px-3 lg:px-4 py-2 lg:py-2.5 hover:bg-black/5 cursor-pointer border-b last:border-0 text-xs lg:text-sm"
                                                @click.prevent="select(item)">
                                                <div class="font-medium text-ink" x-text="item.text"></div>
                                                <div class="text-xs text-ink-muted" x-text="item.subtext"></div>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </div>

                            <div class="text-xs text-ink-muted" x-show="!isTransient && query.length > 0 && autocompleteResults.length === 0 && !autocompleteLoading" x-cloak>
                                No household found.
                            </div>

                            @error('household_id')
                                <p class="text-danger text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div x-show="creating" x-cloak class="bg-teal-soft border border-border rounded-2xl p-4">
                        <div class="mb-4">
                            <h4 class="text-sm font-semibold text-ink">New household details</h4>
                            <p class="text-xs text-ink-muted">Fill in the household information that will be created with this patient.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:gap-4">
                            <div>
                                <label class="block text-xs uppercase font-bold text-ink mb-1">
                                    Zone <span class="text-danger">*</span>
                                </label>
                                <select name="new_household_zone_id"
                                        class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('new_household_zone_id') border-danger bg-danger-soft @else border-border @enderror focus:ring-accent-blue focus:border-accent-blue text-sm">
                                    <option value="">Select zone</option>
                                    @foreach ($zones as $zone)
                                        <option value="{{ $zone->id }}" {{ old('new_household_zone_id') == $zone->id ? 'selected' : '' }}>
                                            {{ $zone->zone_number }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('new_household_zone_id')
                                    <p class="text-danger text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-xs uppercase font-bold text-ink mb-1">
                                    Family Head (Apelyido) <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       name="new_household_family_name_head"
                                       x-model="newFamilyHead"
                                       class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('new_household_family_name_head') border-danger bg-danger-soft @else border-border @enderror focus:ring-accent-blue focus:border-accent-blue text-sm"
                                       placeholder="Head Surname">
                                @error('new_household_family_name_head')
                                    <p class="text-danger text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs uppercase font-bold text-ink mb-1">
                                Contact Number
                            </label>
                            <input type="text"
                                   name="new_household_contact_number"
                                   value="{{ old('new_household_contact_number') }}"
                                   class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('new_household_contact_number') border-danger bg-danger-soft @else border-border @enderror focus:ring-accent-blue focus:border-accent-blue text-sm"
                                   placeholder="e.g. 09XXXXXXXXX">
                            @error('new_household_contact_number')
                                <p class="text-danger text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <p class="text-xs text-ink-muted bg-accent-blue-soft border border-accent-blue/20 rounded-lg p-3">
                            A new household will be created and the patient will be added to it.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="sticky bottom-0 z-40 -mx-4 -mb-4 px-4 py-3 border-t flex flex-wrap justify-end gap-2 lg:gap-3 bg-surface/95 backdrop-blur" style="border-color: var(--border);">
            <a href="{{ route('patients.index') }}" class="px-4 lg:px-6 py-2 rounded-xl border border-border text-xs lg:text-sm font-medium" style="color: var(--ink-muted);">Cancel</a>
            <button type="submit" class="px-5 lg:px-6 py-2 lg:py-2.5 rounded-xl text-white font-semibold text-xs lg:text-sm shadow-md transition" style="background: var(--primary);">
                Save Patient Record
            </button>
        </div>
    </form>

    <script>
        function patientEnroll() {
            return {
                isPhilhealthMember: @js(old('is_philhealth_member', 'n')),
                creating: @js((bool) old('create_new_household')),
                surname: @js(old('last_name', '')),
                surnameMatches: [],
                surnameSearching: false,
                surnameSelected: null,
                query: @js($selectedHousehold?->family_name_head ?? ''),
                householdId: @js($selectedHouseholdId ? (int) $selectedHouseholdId : null),
                transientHouseholdId: @js($transientHouseholdId ?? null),
                transientHouseholdLabel: @js($transientHouseholdLabel ?? 'Transient/Unmapped'),
                isTransient: @js($selectedHouseholdId !== null && $transientHouseholdId !== null && (string) $selectedHouseholdId === (string) $transientHouseholdId),
                previousHouseholdId: null,
                previousQuery: null,
                dropdownOpen: false,
                autocompleteResults: [],
                autocompleteLoading: false,
                newFamilyHead: @js(old('new_household_family_name_head', '')),

                init() {
                    // If the old household is the transient household, lock it in.
                    if (this.isTransient && this.transientHouseholdId !== null) {
                        this.householdId = this.transientHouseholdId;
                        this.query = this.transientHouseholdLabel;
                    }
                },

                async searchSurnameMatches() {
                    if (this.surname.trim().length < 2) {
                        this.surnameMatches = [];
                        return;
                    }
                    if (this.creating && ! this.newFamilyHead.trim()) {
                        this.newFamilyHead = this.surname.trim();
                    }
                    this.surnameSearching = true;
                    try {
                        const url = @json(route('search.households'))
                            + '?query=' + encodeURIComponent(this.surname.trim());
                        const response = await safeFetch(url);
                        const data = response.ok ? await response.json() : [];
                        this.surnameMatches = Array.isArray(data) ? data : [];
                    } catch (e) {
                        this.surnameMatches = [];
                    }
                    this.surnameSearching = false;
                },

                attachSurname(household) {
                    this.surnameSelected = household;
                    this.householdId = household.id;
                    this.query = household.text;
                    this.creating = false;
                    this.surnameMatches = [];
                },

                detachSurname() {
                    this.surnameSelected = null;
                    this.householdId = null;
                    this.query = '';
                },

                async search() {
                    if (this.isTransient) return;
                    const q = (this.query || '').trim();
                    if (q.length < 2) {
                        this.autocompleteResults = [];
                        this.dropdownOpen = false;
                        return;
                    }

                    this.autocompleteLoading = true;
                    try {
                        const response = await safeFetch(`{{ route('search.households') }}?query=${encodeURIComponent(q)}`);
                        this.autocompleteResults = response.ok ? await response.json() : [];
                        this.dropdownOpen = this.autocompleteResults.length > 0;
                    } catch (e) {
                        this.autocompleteResults = [];
                    } finally {
                        this.autocompleteLoading = false;
                    }
                },

                select(item) {
                    this.householdId = item.id;
                    this.query = item.text;
                    this.autocompleteResults = [];
                    this.dropdownOpen = false;
                    this.surnameSelected = null;
                },

                enableCreating() {
                    this.creating = true;
                    if (! this.newFamilyHead.trim() && this.surname.trim().length >= 2) {
                        this.newFamilyHead = this.surname.trim();
                    }
                },

                onTransientToggle() {
                    this.autocompleteResults = [];
                    this.dropdownOpen = false;

                    if (this.isTransient) {
                        this.previousHouseholdId = this.householdId;
                        this.previousQuery = this.query;

                        if (this.transientHouseholdId === null) {
                            this.isTransient = false;
                            return;
                        }

                        this.householdId = this.transientHouseholdId;
                        this.query = this.transientHouseholdLabel;
                        return;
                    }

                    if (this.previousHouseholdId !== null) {
                        this.householdId = this.previousHouseholdId;
                        this.query = this.previousQuery ?? this.query;
                    }
                },
            };
        }
    </script>
</div>
@endsection

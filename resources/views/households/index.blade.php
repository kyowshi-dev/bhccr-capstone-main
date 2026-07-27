@extends('layouts.app')

@section('content')
<div class="space-y-5 lg:space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">
                Household
            </h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">
                Registered households by zone for barangay census and patient mapping.
            </p>
        </div>

        <a href="{{ route('patients.create') }}"
           class="inline-flex items-center justify-center px-4 lg:px-5 py-2 lg:py-2.5 rounded-xl text-xs lg:text-sm font-semibold text-white transition"
           style="background: var(--primary); box-shadow: var(--shadow-sm);">
            + Add household
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-xl border px-3 lg:px-4 py-2 text-xs lg:text-sm"
             style="background: var(--teal-soft); border-color: var(--border); color: var(--primary);">
            {{ session('success') }}
        </div>
    @endif
    
    @if (session('error'))
        <div class="rounded-xl border px-3 lg:px-4 py-2 text-xs lg:text-sm"
             style="background: var(--red-soft); border-color: var(--border); color: var(--red);">
            {{ session('error') }}
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Households -->
        <div class="rounded-xl border p-4" style="background: var(--bg-surface-elevated); border-color: var(--border); box-shadow: var(--shadow-sm);">
            <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">Total Households</p>
            <p class="text-2xl lg:text-3xl font-bold mt-2" style="color: var(--ink);">{{ $totalHouseholds }}</p>
        </div>

        <!-- Total Population -->
        <div class="rounded-xl border p-4" style="background: var(--bg-surface-elevated); border-color: var(--border); box-shadow: var(--shadow-sm);">
            <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">Total Population</p>
            <p class="text-2xl lg:text-3xl font-bold mt-2" style="color: var(--primary);">{{ $totalPopulation }}</p>
        </div>

        <!-- Infants -->
        <div class="rounded-xl border p-4" style="background: var(--bg-surface-elevated); border-color: var(--border); box-shadow: var(--shadow-sm);">
            <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">Infants (0-1yr)</p>
            <p class="text-2xl lg:text-3xl font-bold mt-2" style="color: var(--primary);">{{ $vulnerableGroups['infants'] ?? 0 }}</p>
        </div>

        <!-- Seniors -->
        <div class="rounded-xl border p-4" style="background: var(--bg-surface-elevated); border-color: var(--border); box-shadow: var(--shadow-sm);">
            <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">Seniors (65+)</p>
            <p class="text-2xl lg:text-3xl font-bold mt-2" style="color: var(--primary);">{{ $vulnerableGroups['seniors'] ?? 0 }}</p>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="rounded-xl border p-4" style="background: var(--bg-surface-elevated); border-color: var(--border); box-shadow: var(--shadow-sm);">
        <form method="GET" action="{{ route('households.index') }}" class="space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Search Input -->
                <div>
                    <label class="block text-xs font-semibold mb-1" style="color: var(--ink-muted);">Search</label>
                    <input type="text"
                           name="search"
                           placeholder="Family name or contact..."
                           value="{{ $search }}"
                           class="w-full px-3 py-2 rounded-lg border text-sm"
                           style="border-color: var(--border); background: var(--bg-surface);">
                </div>

                <!-- Zone Filter -->
                <div>
                    <label class="block text-xs font-semibold mb-1" style="color: var(--ink-muted);">Zone</label>
                    <select name="zone_id" class="w-full px-3 py-2 rounded-lg border text-sm"
                            style="border-color: var(--border); background: var(--bg-surface);">
                        <option value="">All zones</option>
                        @foreach ($zones as $zone)
                            <option value={{ $zone_id == $zone->id ? 'selected' : '' }}>
                                {{ $zone->zone_number }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Date From -->
                <div>
                    <label class="block text-xs font-semibold mb-1" style="color: var(--ink-muted);">From Date</label>
                    <input type="date"
                           name="date_from"
                           value="{{ $date_from }}"
                           class="w-full px-3 py-2 rounded-lg border text-sm"
                           style="border-color: var(--border); background: var(--bg-surface);">
                </div>

                <!-- Date To -->
                <div>
                    <label class="block text-xs font-semibold mb-1" style="color: var(--ink-muted);">To Date</label>
                    <input type="date"
                           name="date_to"
                           value="{{ $date_to }}"
                           class="w-full px-3 py-2 rounded-lg border text-sm"
                           style="border-color: var(--border); background: var(--bg-surface);">
                </div>
            </div>

            <div class="flex gap-2 justify-end">
                <a href="{{ route('households.index') }}"
                   class="inline-flex items-center justify-center px-3 py-2 rounded-lg text-xs font-semibold transition"
                   style="background: var(--bg-surface); border: 1px solid var(--border); color: var(--ink);">
                    Clear Filters
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-xs font-semibold text-white transition"
                        style="background: var(--primary);">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Bulk Actions Bar (Hidden by default) -->
    <div id="bulkActionsBar" class="hidden rounded-xl border p-4 gap-3 flex items-center" 
         style="background: var(--primary-soft); border-color: var(--border); box-shadow: var(--shadow-sm);">
        <div class="flex-1">
            <p class="text-sm font-semibold" style="color: var(--primary);"><span id="selectedCount">0</span> selected</p>
        </div>
        <form id="csvExportForm" method="POST" action="{{ route('households.export.csv') }}" style="display: inline;">
            @csrf
            <button type="button" onclick="submitBulkAction('csvExportForm')"
                    class="inline-flex items-center justify-center px-3 py-2 rounded-lg text-xs font-semibold transition"
                    style="background: var(--bg-surface); border: 1px solid var(--border); color: var(--ink);">
                Export CSV
            </button>
        </form>
        <form id="pdfExportForm" method="POST" action="{{ route('households.export.pdf') }}" style="display: inline;">
            @csrf
            <button type="button" onclick="submitBulkAction('pdfExportForm')"
                    class="inline-flex items-center justify-center px-3 py-2 rounded-lg text-xs font-semibold transition"
                    style="background: var(--bg-surface); border: 1px solid var(--border); color: var(--ink);">
                Export PDF
            </button>
        </form>
        <button type="button" onclick="openZoneModal()"
                class="inline-flex items-center justify-center px-3 py-2 rounded-lg text-xs font-semibold transition"
                style="background: var(--primary); color: white;">
                Reassign Zone
        </button>
        <button type="button" onclick="clearSelection()"
                class="inline-flex items-center justify-center px-3 py-2 rounded-lg text-xs font-semibold transition"
                style="background: var(--bg-surface); border: 1px solid var(--border); color: var(--ink);">
                ✕ Clear
        </button>
    </div>

    <!-- Households Table -->
    <div class="rounded-2xl border overflow-hidden"
         style="background: var(--bg-surface-elevated); border-color: var(--border); box-shadow: var(--shadow-sm);">
        <div class="px-4 lg:px-5 py-3 border-b flex justify-between items-center" 
             style="border-color: var(--border); background: var(--bg-surface);">
            <p class="text-xs lg:text-sm" style="color: var(--ink-muted);">
                <span class="font-semibold" style="color: var(--ink);">{{ $households->count() }}</span> households shown
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-xs lg:text-sm">
                <thead>
                    <tr style="background: var(--teal-soft);">
                        <th class="px-3 py-2.5 lg:py-3 font-semibold" style="color: var(--ink-muted); width: 40px;">
                            <input type="checkbox" id="selectAllCheckbox" class="rounded" 
                                   onchange="toggleSelectAll(this)">
                        </th>
                        <th class="px-3 lg:px-4 py-2.5 lg:py-3 font-semibold uppercase tracking-wide"
                            style="color: var(--ink-muted);">
                            Zone
                        </th>
                        <th class="px-3 lg:px-4 py-2.5 lg:py-3 font-semibold uppercase tracking-wide"
                            style="color: var(--ink-muted);">
                            Family name (head)
                        </th>
                        <th class="px-3 lg:px-4 py-2.5 lg:py-3 font-semibold uppercase tracking-wide"
                            style="color: var(--ink-muted);">
                            Contact number
                        </th>
                        <th class="px-3 lg:px-4 py-2.5 lg:py-3 font-semibold uppercase tracking-wide text-center"
                            style="color: var(--ink-muted);">
                            Members
                        </th>
                        <th class="px-3 lg:px-4 py-2.5 lg:py-3 font-semibold uppercase tracking-wide"
                            style="color: var(--ink-muted);">
                            Registered at
                        </th>
                        <th class="px-3 lg:px-4 py-2.5 lg:py-3 text-right font-semibold uppercase tracking-wide"
                            style="color: var(--ink-muted);">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($households as $household)
                        <tr class="border-b last:border-b-0 household-row" style="border-color: var(--border);">
                            <td class="px-3 py-2.5 lg:py-3">
                                <input type="checkbox" class="household-checkbox rounded" value="{{ $household->id }}"
                                       onchange="updateBulkActionsBar()">
                            </td>
                            <td class="px-3 lg:px-4 py-2.5 lg:py-3" style="color: var(--ink);">
                                {{ $household->zone_number }}
                            </td>
                            <td class="px-3 lg:px-4 py-2.5 lg:py-3 font-medium" style="color: var(--ink);">
                                {{ ucfirst($household->family_name_head) }}
                            </td>
                            <td class="px-3 lg:px-4 py-2.5 lg:py-3" style="color: var(--ink-muted);">
                                {{ $household->contact_number ?: '—' }}
                            </td>
                            <td class="px-3 lg:px-4 py-2.5 lg:py-3 text-center" style="color: var(--ink-muted);">
                                <span class="inline-flex items-center justify-center px-2 py-1 rounded-full text-xs font-semibold"
                                      style="background: var(--primary-soft); color: var(--primary);">
                                    {{ $memberCounts[$household->id] ?? 0 }}
                                </span>
                            </td>
                            <td class="px-3 lg:px-4 py-2.5 lg:py-3" style="color: var(--ink-muted);">
                                {{ $household->created_at ? \Illuminate\Support\Carbon::parse($household->created_at)->format('M d, Y') : '—' }}
                            </td>
                            <td class="px-3 lg:px-4 py-2.5 lg:py-3 text-right whitespace-nowrap space-x-1 flex justify-end items-center">
                                <a href="{{ route('households.edit', $household->id) }}"
                                   class="inline-flex items-center justify-center px-2 py-1.5 rounded-lg text-[11px] font-semibold transition"
                                   style="background: var(--primary-soft); color: var(--primary); border: 1px solid var(--border);"
                                   title="Edit household">
                                    ✎ Edit
                                </a>
                                
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <div class="flex justify-center mb-3"><i class="fa-solid fa-house text-3xl" style="color: var(--ink-subtle);"></i></div>
                                <p class="text-sm font-medium" style="color: var(--ink);">No households found</p>
                                <p class="text-xs mt-1 mb-3" style="color: var(--ink-muted);">Start by registering a household to map your barangay population</p>
                                <a href="{{ route('patients.create') }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold text-white transition hover:opacity-90" style="background: var(--primary);"><i class="fa-solid fa-plus"></i> Add household</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 lg:px-5 py-3 border-t" style="border-color: var(--border); background: var(--bg-surface);">
            {{ $households->links() }}
        </div>
    </div>
</div>

<!-- Zone Reassignment Modal -->
<div id="zoneModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 max-w-md w-full mx-4"
         style="background: var(--bg-surface-elevated);">
        <h2 class="text-lg font-semibold mb-4" style="color: var(--ink);">Reassign Zone</h2>
        <form id="zoneReassignForm" method="POST" action="{{ route('households.update-zone') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-semibold mb-2" style="color: var(--ink-muted);">Select New Zone</label>
                <select name="new_zone_id" required
                        class="w-full px-3 py-2 rounded-lg border text-sm"
                        style="border-color: var(--border); background: var(--bg-surface);">
                    <option value="">--- Select Zone ---</option>
                    @foreach ($zones as $zone)
                        <option value="{{ $zone->id }}">Zone {{ $zone->zone_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" onclick="closeZoneModal()"
                        class="px-4 py-2 rounded-lg text-sm font-semibold transition"
                        style="background: var(--bg-surface); border: 1px solid var(--border); color: var(--ink);">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition"
                        style="background: var(--primary);">
                    Reassign
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.household-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    updateBulkActionsBar();
}

function updateBulkActionsBar() {
    const checkboxes = document.querySelectorAll('.household-checkbox:checked');
    const count = checkboxes.length;
    const bar = document.getElementById('bulkActionsBar');
    document.getElementById('selectedCount').textContent = count;
    
    if (count > 0) {
        bar.classList.remove('hidden');
    } else {
        bar.classList.add('hidden');
    }
}

function clearSelection() {
    document.getElementById('selectAllCheckbox').checked = false;
    document.querySelectorAll('.household-checkbox').forEach(cb => cb.checked = false);
    updateBulkActionsBar();
}

function submitBulkAction(formId) {
    const checkboxes = document.querySelectorAll('.household-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one household.');
        return;
    }
    
    const form = document.getElementById(formId);
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'household_ids[]';
    
    checkboxes.forEach(cb => {
        const hiddenInput = form.querySelector(`input[name*="household_ids"][value="${cb.value}"]`);
        if (!hiddenInput) {
            const inputClone = input.cloneNode(true);
            inputClone.value = cb.value;
            form.appendChild(inputClone);
        }
    });
    
    // Clear any existing inputs and add fresh ones
    form.querySelectorAll('input[name*="household_ids"]').forEach(el => el.remove());
    checkboxes.forEach(cb => {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'household_ids[]';
        hiddenInput.value = cb.value;
        form.appendChild(hiddenInput);
    });
    
    form.submit();
}

function openZoneModal() {
    const checkboxes = document.querySelectorAll('.household-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one household.');
        return;
    }

    const form = document.getElementById('zoneReassignForm');
    form.querySelectorAll('input[name="household_ids[]"]').forEach(el => el.remove());

    const ids = Array.from(checkboxes).map(cb => cb.value);
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'household_ids[]';
        input.value = id;
        form.appendChild(input);
    });

    document.getElementById('zoneModal').classList.remove('hidden');
}

function closeZoneModal() {
    document.getElementById('zoneModal').classList.add('hidden');
}

window.addEventListener('click', function(event) {
    const modal = document.getElementById('zoneModal');
    if (event.target === modal) {
        closeZoneModal();
    }
});
</script>
@endsection


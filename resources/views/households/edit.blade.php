@extends('layouts.app')

@section('content')
<div class="space-y-5 lg:space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">
                Edit Household
            </h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">
                Update household information and contact details.
            </p>
        </div>

        <a href="{{ route('households.index') }}"
           class="inline-flex items-center justify-center px-4 lg:px-5 py-2 lg:py-2.5 rounded-xl text-xs lg:text-sm font-semibold transition"
           style="background: var(--bg-surface); border: 1px solid var(--border); color: var(--ink);">
            ← Back to Households
        </a>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border px-4 py-3 text-sm"
             style="background: var(--red-soft); border-color: var(--border); color: var(--red);">
            <p class="font-semibold mb-2">Validation errors:</p>
            <ul class="list-disc list-inside space-y-1 text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form -->
    <div class="rounded-2xl border overflow-hidden"
         style="background: var(--bg-surface-elevated); border-color: var(--border); box-shadow: var(--shadow-sm);">
        <form method="POST" action="{{ route('households.update', $household->id) }}" class="space-y-4 p-4 lg:p-6">
            @csrf
            @method('PUT')

            <!-- Zone Dropdown -->
            <div>
                <label for="zone_id" class="block text-sm font-semibold mb-2" style="color: var(--ink-muted);">
                    Zone <span style="color: var(--red);">*</span>
                </label>
                <select id="zone_id" name="zone_id" required
                        class="w-full px-3 py-2 rounded-lg border text-sm"
                        style="border-color: var(--border); background: var(--bg-surface);">
                    <option value="">Select a zone</option>
                </select>
                @error('zone_id')
                    <p class="text-xs mt-1" style="color: var(--red);">{{ $message }}</p>
                @enderror
            </div>

            <!-- Family Name -->
            <div>
                <label for="family_name_head" class="block text-sm font-semibold mb-2" style="color: var(--ink-muted);">
                    Family Name (Head) <span style="color: var(--red);">*</span>
                </label>
                <input type="text" id="family_name_head" name="family_name_head"
                       value="{{ old('family_name_head', $household->family_name_head) }}"
                       required maxlength="255"
                       class="w-full px-3 py-2 rounded-lg border text-sm"
                       style="border-color: var(--border); background: var(--bg-surface);">
                @error('family_name_head')
                    <p class="text-xs mt-1" style="color: var(--red);">{{ $message }}</p>
                @enderror
            </div>

            <!-- Contact Number -->
            <div>
                <label for="contact_number" class="block text-sm font-semibold mb-2" style="color: var(--ink-muted);">
                    Contact Number (Optional)
                </label>
                <input type="text" id="contact_number" name="contact_number"
                       value="{{ old('contact_number', $household->contact_number) }}"
                       maxlength="32" placeholder="e.g., +63-9171234567"
                       class="w-full px-3 py-2 rounded-lg border text-sm"
                       style="border-color: var(--border); background: var(--bg-surface);">
                <p class="text-xs mt-1" style="color: var(--ink-muted);">Allowed: digits, spaces, +, - and ()</p>
                @error('contact_number')
                    <p class="text-xs mt-1" style="color: var(--red);">{{ $message }}</p>
                @enderror
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3 justify-end pt-4 border-t" style="border-color: var(--border);">
                <a href="{{ route('households.index') }}"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-semibold transition"
                   style="background: var(--bg-surface); border: 1px solid var(--border); color: var(--ink);">
                    Cancel
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-semibold text-white transition"
                        style="background: var(--primary);">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Load zones from server via API or inline
document.addEventListener('DOMContentLoaded', function() {
    const zoneSelect = document.getElementById('zone_id');
    const currentZoneId = {{ $household->zone_id }};
    
    // Zones should be passed from controller or fetched via AJAX
    // For now, we'll use inline data if available
    const zones = @json($zones ?? []);
    
    zones.forEach(zone => {
        const option = document.createElement('option');
        option.value = zone.id;
        option.textContent = 'Zone ' + zone.zone_number;
        if (zone.id == currentZoneId) {
            option.selected = true;
        }
        zoneSelect.appendChild(option);
    });
});
</script>
@endsection

@php
    $fieldName = $fieldName ?? 'consultation_id';
    $label = $label ?? 'Origin consultation';
    $selected = $selected ?? null;
    $consultations = $consultations ?? collect();
    $xModel = $xModel ?? null;
    $nullable = $nullable ?? true;
@endphp
<div>
    <label for="{{ $fieldName }}" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">
        {{ $label }} <span class="text-xs font-normal" style="color: var(--ink-subtle);">(optional)</span>
    </label>
    <select id="{{ $fieldName }}" name="{{ $fieldName }}" @if ($xModel) x-model="{{ $xModel }}" @endif
           class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
           style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
        @if ($nullable)
            <option value="">- None -</option>
        @endif
        @forelse ($consultations as $consultation)
            <option value="{{ $consultation->id }}" @selected((string) $selected === (string) $consultation->id)>
                #{{ $consultation->id }} · {{ optional($consultation->created_at)->format('M d, Y') }} · {{ $consultation->purpose_of_visit ?? $consultation->nature_of_visit ?? 'Consultation' }}
            </option>
        @empty
            <option value="" disabled>No prior consultations</option>
        @endforelse
    </select>
    @error($fieldName) <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p> @enderror
</div>
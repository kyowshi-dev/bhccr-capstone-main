@extends('layouts.app')

@section('title', $medicine->medicine_name)

@section('content')
<div class="space-y-5 lg:space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <a href="{{ route('medicines.index') }}" class="text-sm font-medium hover:underline mb-1 inline-block" style="color: var(--primary);">← Back to medicines</a>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">{{ $medicine->medicine_name }}</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Medicine details</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('medicines.edit', $medicine->id) }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition duration-200 hover:shadow-md" style="background: var(--primary);">
                Edit
            </a>
            <form action="{{ route('medicines.destroy', $medicine->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this medicine?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition duration-200 hover:shadow-md" style="background: var(--red);">
                    Delete
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="rounded-xl border p-5 lg:p-6" style="background: var(--bg-surface); border-color: var(--border);">
            <h2 class="font-display font-semibold text-lg mb-4" style="color: var(--ink);">Details</h2>
            <dl class="space-y-3">
                <div>
                    <dt class="text-xs font-medium" style="color: var(--ink-muted);">Medicine Name</dt>
                    <dd style="color: var(--ink);">{{ $medicine->medicine_name }}</dd>
                </div>
                @if ($medicine->category)
                    <div>
                        <dt class="text-xs font-medium" style="color: var(--ink-muted);">Category</dt>
                        <dd style="color: var(--ink);">{{ $medicine->category }}</dd>
                    </div>
                @endif
                @if ($medicine->description)
                    <div>
                        <dt class="text-xs font-medium" style="color: var(--ink-muted);">Description</dt>
                        <dd style="color: var(--ink);">{{ $medicine->description }}</dd>
                    </div>
                @endif
                @if ($medicine->expiration_date)
                    <div>
                        <dt class="text-xs font-medium" style="color: var(--ink-muted);">Expiration Date</dt>
                        <dd style="color: var(--ink);">
                            {{ \Carbon\Carbon::parse($medicine->expiration_date)->format('M d, Y') }}
                            @if (\Carbon\Carbon::parse($medicine->expiration_date)->isPast())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-red-100 text-red-800 w-fit ml-2">
                                    Expired
                                </span>
                            @endif
                        </dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="rounded-xl border p-5 lg:p-6" style="background: var(--bg-surface); border-color: var(--border);">
            <h2 class="font-display font-semibold text-lg mb-4" style="color: var(--ink);">Usage Statistics</h2>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm" style="color: var(--ink-muted);">Total prescriptions</span>
                    <span class="text-sm font-medium" style="color: var(--ink);">{{ $medicine->prescription_count ?? 0 }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm" style="color: var(--ink-muted);">Last prescribed</span>
                    <span class="text-sm font-medium" style="color: var(--ink);">{{ $medicine->last_prescribed ? \Carbon\Carbon::parse($medicine->last_prescribed)->format('M d, Y') : 'Never' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
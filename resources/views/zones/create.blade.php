@extends('layouts.app')

@section('title', 'Add Zone')

@section('content')
<div class="space-y-5 lg:space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <a href="{{ route('zones.index') }}" class="text-sm font-medium hover:underline mb-1 inline-block" style="color: var(--primary);">← Back to zones</a>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Add zone</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Create a new geographic zone.</p>
        </div>
    </div>

    <div class="rounded-xl border p-5 lg:p-6 max-w-xl" style="background: var(--bg-surface); border-color: var(--border);">
        <form action="{{ route('zones.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="space-y-4">
                <div>
                    <label for="zone_number" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Zone number <span style="color: var(--accent);">*</span></label>
                    <input type="text" id="zone_number" name="zone_number" value="{{ old('zone_number') }}" required class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                    @error('zone_number')<p class="mt-1 text-xs" style="color: var(--accent);">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="assigned_worker_id" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Assign health worker</label>
                    <select id="assigned_worker_id" name="assigned_worker_id" class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                        <option value="">— Select a worker —</option>
                        @foreach ($healthWorkers as $worker)
                            <option value="{{ $worker->id }}" {{ old('assigned_worker_id') == $worker->id ? 'selected' : '' }}>
                                {{ $worker->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('assigned_worker_id')<p class="mt-1 text-xs" style="color: var(--accent);">{{ $message }}</p>@enderror
                </div>
            </div>
            <button type="submit" class="px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition duration-200 hover:shadow-md" style="background: var(--accent);">
                Add zone
            </button>
        </form>
    </div>
</div>
@endsection

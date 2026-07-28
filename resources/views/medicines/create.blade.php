@extends('layouts.app')

@section('title', 'Add Medicine')

@section('content')
<div class="space-y-5 lg:space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <a href="{{ route('medicines.index') }}" class="text-sm font-medium hover:underline mb-1 inline-block" style="color: var(--primary);">← Back to medicines</a>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Add medicine</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Add a new medicine to the system.</p>
        </div>
    </div>

    <div class="rounded-xl border p-5 lg:p-6 max-w-xl" style="background: var(--bg-surface); border-color: var(--border);">
        <form action="{{ route('medicines.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Medicine name</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                    @error('name')<p class="mt-1 text-xs" style="color: var(--accent);">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="generic_name" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Generic name</label>
                    <input type="text" id="generic_name" name="generic_name" value="{{ old('generic_name') }}" class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                    @error('generic_name')<p class="mt-1 text-xs" style="color: var(--accent);">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="strength" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Strength</label>
                    <input type="text" id="strength" name="strength" value="{{ old('strength') }}" class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                    @error('strength')<p class="mt-1 text-xs" style="color: var(--accent);">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="form" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Form</label>
                    <input type="text" id="form" name="form" value="{{ old('form') }}" class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                    @error('form')<p class="mt-1 text-xs" style="color: var(--accent);">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="manufacturer" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Manufacturer</label>
                    <input type="text" id="manufacturer" name="manufacturer" value="{{ old('manufacturer') }}" class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                    @error('manufacturer')<p class="mt-1 text-xs" style="color: var(--accent);">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="expiration_date" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Expiration date</label>
                    <input type="date" id="expiration_date" name="expiration_date" value="{{ old('expiration_date') }}" class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                    @error('expiration_date')<p class="mt-1 text-xs" style="color: var(--accent);">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                    <label for="is_active" class="text-sm" style="color: var(--ink-muted);">Active</label>
                    @error('is_active')<p class="mt-1 text-xs" style="color: var(--accent);">{{ $message }}</p>@enderror
                </div>
            </div>
            <button type="submit" class="px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition duration-200 hover:shadow-md" style="background: var(--accent);">
                Add medicine
            </button>
        </form>
    </div>
</div>
@endsection
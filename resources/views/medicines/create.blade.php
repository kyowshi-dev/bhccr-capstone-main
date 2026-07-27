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
                    <label for="medicine_name" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Medicine name</label>
                    <input type="text" id="medicine_name" name="medicine_name" value="{{ old('medicine_name') }}" required class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                    @error('medicine_name')<p class="mt-1 text-xs" style="color: var(--accent);">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="category" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Category</label>
                    <input type="text" id="category" name="category" value="{{ old('category') }}" class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                    @error('category')<p class="mt-1 text-xs" style="color: var(--accent);">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="description" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Description</label>
                    <textarea id="description" name="description" rows="3" class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">{{ old('description') }}</textarea>
                    @error('description')<p class="mt-1 text-xs" style="color: var(--accent);">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="expiration_date" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Expiration date</label>
                    <input type="date" id="expiration_date" name="expiration_date" value="{{ old('expiration_date') }}" class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                    @error('expiration_date')<p class="mt-1 text-xs" style="color: var(--accent);">{{ $message }}</p>@enderror
                </div>
            </div>
            <button type="submit" class="px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition duration-200 hover:shadow-md" style="background: var(--accent);">
                Add medicine
            </button>
        </form>
    </div>
</div>
@endsection
@extends('layouts.app')

@section('title', 'Edit Medicine')

@section('content')
<div class="space-y-5 lg:space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <a href="{{ route('medicines.show', $medicine->id) }}" class="text-sm font-medium hover:underline mb-1 inline-block" style="color: var(--primary);">← Back to medicine</a>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Edit medicine</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Update medicine details.</p>
        </div>
    </div>

    <div class="rounded-xl border p-5 lg:p-6 max-w-xl" style="background: var(--bg-surface); border-color: var(--border);">
        <form action="{{ route('medicines.update', $medicine->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Medicine name</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $medicine->name) }}" required class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                    @error('name')<p class="mt-1 text-xs" style="color: var(--accent);">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="form" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Form</label>
                    <input type="text" id="form" name="form" value="{{ old('form', $medicine->form) }}" class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                    @error('form')<p class="mt-1 text-xs" style="color: var(--accent);">{{ $message }}</p>@enderror
                </div>
            </div>
            <button type="submit" class="px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition duration-200 hover:shadow-md" style="background: var(--accent);">
                Update medicine
            </button>
        </form>
    </div>
</div>
@endsection
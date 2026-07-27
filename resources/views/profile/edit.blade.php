@extends('layouts.app')

@section('title', 'Edit Profile')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <!-- Back Button -->
    <a href="{{ route('profile.show') }}" class="inline-flex items-center gap-2 text-sm font-medium" style="color: var(--primary);">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
        </svg>
        Back to Profile
    </a>

    <!-- Edit Profile Form -->
    <div class="rounded-2xl p-6 border border-[var(--border)]" style="background: var(--bg-surface-elevated); box-shadow: var(--shadow-sm);">
        <h1 class="text-2xl font-display font-semibold mb-6" style="color: var(--ink);">Edit Your Profile</h1>

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-lg" style="background: rgba(196, 92, 65, 0.1); border: 1px solid rgba(196, 92, 65, 0.2);">
                <h3 class="font-semibold text-sm mb-2" style="color: #c45c41;">Please fix the following errors:</h3>
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li class="text-sm" style="color: #c45c41;">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Profile Photo -->
            <div>
                <label class="block text-sm font-semibold mb-3" style="color: var(--ink);">Profile Photo</label>
                <div class="flex items-end gap-4">
                    <div class="h-24 w-24 rounded-full flex items-center justify-center text-2xl font-semibold flex-shrink-0"
                         style="background: var(--teal-soft); color: var(--primary);" id="photoPreview">
                        @if($user->profile_photo_path)
                            <img src="{{ Storage::url($user->profile_photo_path) }}" alt="{{ $user->username }}" class="h-24 w-24 rounded-full object-cover">
                        @else
                            {{ mb_strtoupper(mb_substr($user->username, 0, 1)) }}
                        @endif
                    </div>
                    <div class="flex-1">
                        <input type="file" name="profile_photo" id="profile_photo" accept="image/*" class="block w-full text-sm border border-[var(--border)] rounded-lg p-2.5 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2" style="focus:ring-color: var(--primary);">
                        <p class="text-xs mt-2" style="color: var(--ink-subtle);">JPG, PNG or GIF. Max 5MB.</p>
                    </div>
                </div>
            </div>

            <!-- Bio -->
            <div>
                <label for="bio" class="block text-sm font-semibold mb-2" style="color: var(--ink);">Bio</label>
                <textarea name="bio" id="bio" rows="4" class="block w-full border border-[var(--border)] rounded-lg p-3 text-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2" style="focus:ring-color: var(--primary);" placeholder="Tell us about yourself...">{{ old('bio', $user->bio) }}</textarea>
                <p class="text-xs mt-2" style="color: var(--ink-subtle);">Maximum 500 characters.</p>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3 pt-4">
                <button type="submit" class="px-6 py-2.5 rounded-lg text-sm font-medium transition-colors duration-200" style="background: var(--primary); color: white;">
                    Save Changes
                </button>
                <a href="{{ route('profile.show') }}" class="px-6 py-2.5 rounded-lg text-sm font-medium border border-[var(--border)] transition-colors duration-200" style="color: var(--ink);">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('profile_photo').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const preview = document.getElementById('photoPreview');
                preview.innerHTML = `<img src="${event.target.result}" alt="Preview" class="h-24 w-24 rounded-full object-cover">`;
            };
            reader.readAsDataURL(file);
        }
    });
</script>
@endsection

@extends('layouts.app')

@section('title', 'Backup Settings')

@section('content')
<div class="space-y-5 lg:space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Backups</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Export the database for backup or transfer to another server.</p>
        </div>
        <a href="{{ route('settings.index') }}" class="text-sm font-medium transition hover:underline" style="color: var(--primary);">Back to Settings</a>
    </div>

    @if (session('success'))
        <div class="rounded-xl border px-4 py-3" style="background: var(--teal-soft); border-color: var(--primary); color: var(--primary);">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="rounded-xl border px-4 py-3" style="background: var(--danger-soft); border-color: var(--danger-soft); color: var(--danger);">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-xl border p-5 lg:p-6" style="background: var(--bg-surface); border-color: var(--border);">
            <h2 class="font-display font-semibold text-lg mb-4" style="color: var(--ink);">Export Database</h2>
            <p class="text-sm mb-4" style="color: var(--ink-muted);">Current database: <strong style="color: var(--ink);">{{ $databaseName }}</strong> ({{ $driver }}). Download a full copy now and store it in a safe place for disaster recovery.</p>
            <form action="{{ route('settings.backups.export') }}" method="POST" class="inline-flex flex-col items-start gap-3" data-disable-on-submit data-no-loading>
                @csrf
                <div class="w-full">
                    <label for="current_password_export" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Confirm your password</label>
                    <input type="password" id="current_password_export" name="current_password" autocomplete="current-password" required class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                    @error('current_password')<p class="mt-1 text-xs" style="color: var(--danger);">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="submit-btn px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition duration-200 hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed" style="background: var(--accent);">
                    Download database export
                </button>
            </form>
        </div>

        <div class="rounded-xl border p-5 lg:p-6" style="background: var(--bg-surface); border-color: var(--border);">
            <h2 class="font-display font-semibold text-lg mb-4" style="color: var(--ink);">Import Database</h2>
            <p class="text-sm mb-4" style="color: var(--ink-muted);">Upload a database backup file to restore the system. <strong class="text-danger">Warning:</strong> This will replace all current data.</p>

            <form action="{{ route('settings.backups.import') }}" method="POST" enctype="multipart/form-data" data-disable-on-submit>
                @csrf
                <div class="space-y-4">
                    <div>
                        <label for="backup_file" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Backup File</label>
                        <input type="file" id="backup_file" name="backup_file" accept=".sql,.sqlite,.db" required class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-teal-soft file:text-ink hover:file:bg-teal-soft" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                        @error('backup_file')<p class="mt-1 text-xs" style="color: var(--danger);">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="current_password_import" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Confirm your password</label>
                        <input type="password" id="current_password_import" name="current_password" autocomplete="current-password" required class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                        @error('current_password')<p class="mt-1 text-xs" style="color: var(--danger);">{{ $message }}</p>@enderror
                    </div>

                    <div class="bg-amber-soft border border-amber/30 rounded-lg p-3">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5" style="color: var(--amber);" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium" style="color: var(--amber);">Important Warning</h3>
                                <div class="mt-2 text-sm" style="color: var(--amber);">
                                    <p>Importing a backup will completely replace all current data. A backup of the current database will be created automatically before import.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition duration-200 hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed" style="background: var(--danger);" onclick="event.preventDefault(); Swal.fire({title: 'Import database backup?', text: 'This will replace all current data. A backup of the current database will be saved automatically.', icon: 'warning', showCancelButton: true, confirmButtonColor: 'var(--danger)', cancelButtonColor: '#6b7280', confirmButtonText: 'Import', cancelButtonText: 'Cancel'}).then(result => { if (result.isConfirmed) { const btn = this; btn.disabled = true; btn.closest('form').submit(); } });">
                        Import Database Backup
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-disable-on-submit]').forEach(function (form) {
        form.addEventListener('submit', function () {
            const btn = form.querySelector('.submit-btn');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                if (btn.dataset.loadingText) {
                    btn.textContent = btn.dataset.loadingText;
                }
            }
        });
    });
</script>
@endpush

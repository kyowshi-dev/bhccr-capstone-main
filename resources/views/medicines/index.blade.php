@extends('layouts.app')

@section('title', 'Medicines')

@section('content')
<div class="space-y-5 lg:space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Medicines</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Manage the list of available medicines for prescriptions.</p>
        </div>
        <a href="{{ route('medicines.create') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition duration-200 hover:shadow-md" style="background: var(--primary);">
            Add medicine
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-xl border px-4 py-3" style="background: var(--teal-soft); border-color: var(--primary); color: var(--primary);">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-xl border px-4 py-3" style="background: var(--danger-soft); border-color: var(--danger); color: var(--danger);">
            {{ session('error') }}
        </div>
    @endif

    @if (session('import_errors'))
        <div class="rounded-xl border px-4 py-3" style="background: var(--danger-soft); border-color: var(--danger); color: var(--danger);">
            <strong>Import Errors:</strong>
            <ul class="mt-2 list-disc list-inside">
                @foreach (session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-6">
        <div class="lg:col-span-2">
            <div class="rounded-xl border overflow-hidden" style="background: var(--bg-surface-elevated); border-color: var(--border);">
                <form id="bulk-delete-form" action="{{ route('medicines.bulk-delete') }}" method="POST" onsubmit="return confirmBulkDelete();">
                    @csrf
                    <div id="bulk-actions-bar" class="px-3 lg:px-4 py-3 flex items-center justify-between hidden" style="background: var(--bg-surface-elevated);">
                        <div>
                            <button id="bulk-delete-btn" type="submit" class="inline-flex items-center justify-center px-3 py-2 rounded-lg text-xs font-semibold text-white transition hover:opacity-90" style="background: var(--danger);">Delete Selected</button>
                        </div>
                        <div class="text-sm" style="color: var(--ink-muted);">
                            <span id="selected-count">0</span> selected
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead style="background: var(--teal-soft);">
                                <tr>
                                    <th class="w-10 px-2 py-2 lg:py-3 text-left" style="color: var(--ink-muted);"><input type="checkbox" id="select-all" class="align-middle"></th>
                                    <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium" style="color: var(--ink-muted);">Name</th>
                                    <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium hidden xl:table-cell" style="color: var(--ink-muted);">Form</th>
                                    <th class="px-3 lg:px-4 py-2 lg:py-3 text-right text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--border)]">
                                @forelse ($medicines as $medicine)
                                    <tr class="transition-colors hover:bg-black/[0.02]">
                                        <td class="w-10 px-2 py-2 lg:py-3 text-center" style="color: var(--ink);"><input type="checkbox" name="ids[]" value="{{ $medicine->id }}" class="row-checkbox align-middle"></td>
                                        <td class="px-3 lg:px-4 py-2 lg:py-3" style="color: var(--ink);">{{ $medicine->name ?? '—' }}</td>
                                        <td class="px-3 lg:px-4 py-2 lg:py-3 hidden xl:table-cell" style="color: var(--ink-muted);">{{ $medicine->form ?? '—' }}</td>
                                        <td class="px-3 lg:px-4 py-2 lg:py-3 text-right whitespace-nowrap">
                                            <a href="{{ route('medicines.show', $medicine->id) }}" class="text-sm font-medium hover:underline" style="color: var(--primary);">View</a>
                                            <span class="mx-2" style="color: var(--ink-muted);">·</span>
                                            <a href="{{ route('medicines.edit', $medicine->id) }}" class="text-sm font-medium hover:underline" style="color: var(--primary);">Edit</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-3 lg:px-4 py-12 text-center">
                                            <div class="flex justify-center mb-3"><i class="fa-solid fa-pills text-3xl" style="color: var(--ink-subtle);"></i></div>
                                            <p class="text-sm font-medium" style="color: var(--ink);">No medicines in inventory</p>
                                            <p class="text-xs mt-1 mb-3" style="color: var(--ink-muted);">Add your first medicine or import from CSV to get started</p>
                                            <a href="{{ route('medicines.create') }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold text-white transition hover:opacity-90" style="background: var(--accent);"><i class="fa-solid fa-plus"></i> Add medicine</a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>

            @if ($medicines->hasPages())
                <div class="border-t px-3 lg:px-4 py-3" style="border-color: var(--border);">
                    {{ $medicines->onEachSide(1)->links() }}
                </div>
            @endif
        </div>

        <div>
            <div class="rounded-xl border p-5 lg:p-6" style="background: var(--bg-surface); border-color: var(--border);">
                <h2 class="font-display font-semibold text-lg mb-4" style="color: var(--ink);">Import CSV</h2>
                <p class="text-sm mb-4" style="color: var(--ink-muted);">Upload a CSV file to bulk import medicines. The file should have columns: name (required), form.</p>
                
                <form action="{{ route('medicines.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label for="csv_file" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">CSV File</label>
                        <input type="file" id="csv_file" name="csv_file" accept=".csv" required class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-teal-soft file:text-ink hover:file:bg-teal-soft" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                        @error('csv_file')<p class="mt-1 text-xs" style="color: var(--accent);">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition duration-200 hover:shadow-md" style="background: var(--primary);">
                        Import Medicines
                    </button>
                </form>

                <div class="mt-4">
                    <details class="text-sm">
                        <summary class="cursor-pointer font-medium" style="color: var(--ink-muted);">CSV Format Example</summary>
                        <div class="mt-2 p-3 rounded-lg" style="background: var(--bg-surface-elevated); border: 1px solid var(--border);">
                            <pre class="text-xs" style="color: var(--ink-muted);">name,form
Paracetamol 500mg Tablet,Tablet
Amoxicillin 500mg Capsule,Capsule</pre>
                        </div>
                    </details>
                </div>
            </div>
        </div>
    </div>
        <script>
            (function(){
                function updateSelectedCount(){
                    const checked = document.querySelectorAll('input[name="ids[]"]:checked').length;
                    const bar = document.getElementById('bulk-actions-bar');
                    const count = document.getElementById('selected-count');
                    if(bar) bar.classList.toggle('hidden', checked === 0);
                    if(count) count.textContent = checked;
                }

                document.addEventListener('DOMContentLoaded', function(){
                    const selectAll = document.getElementById('select-all');
                    if(selectAll){
                        selectAll.addEventListener('change', function(e){
                            document.querySelectorAll('input[name="ids[]"]').forEach(cb => cb.checked = e.target.checked);
                            updateSelectedCount();
                        });
                    }

                    document.querySelectorAll('input[name="ids[]"]').forEach(cb => cb.addEventListener('change', function(){
                        const all = document.querySelectorAll('input[name="ids[]"]');
                        const checked = document.querySelectorAll('input[name="ids[]"]:checked');
                        if(selectAll) selectAll.checked = all.length === checked.length && all.length > 0;
                        updateSelectedCount();
                    }));
                });

                window.confirmBulkDelete = function(){
                    const checked = document.querySelectorAll('input[name="ids[]"]:checked').length;
                    if(checked === 0){
                        Swal.fire({title: 'No selection', text: 'Please select at least one medicine to delete.', icon: 'warning', confirmButtonColor: 'var(--primary)'});
                        return false;
                    }
                    Swal.fire({
                        title: 'Delete selected medicines?',
                        text: 'This action cannot be undone.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: 'var(--danger)',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Delete',
                        cancelButtonText: 'Cancel',
                    }).then((result) => {
                        if (result.isConfirmed) document.getElementById('bulk-delete-form').submit();
                    });
                    return false;
                }
            })();
        </script>
    @endsection
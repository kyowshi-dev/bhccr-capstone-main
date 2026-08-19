@extends('layouts.app')

@section('title', 'Medicines')

@section('content')
<div class="space-y-5 lg:space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Medicines</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Manage the list of available medicines for prescriptions.</p>
        </div>
        <div class="flex flex-wrap gap-2 sm:flex-nowrap">
            <a href="{{ route('medicines.create') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition duration-200 hover:shadow-md" style="background: var(--primary);">
                Add medicine
            </a>
        </div>
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

    <details class="group rounded-xl border overflow-hidden" style="background: var(--bg-surface); border-color: var(--border);">
        <summary class="list-none flex flex-wrap items-center gap-3 px-4 lg:px-5 py-3 cursor-pointer select-none [&::-webkit-details-marker]:hidden">
            <i class="fa-solid fa-chevron-right text-xs transition-transform group-open:rotate-90" style="color: var(--ink-muted);" aria-hidden="true"></i>
            <span class="text-sm font-semibold" style="color: var(--ink);">Import medicines from CSV</span>
            <span class="text-xs font-medium" style="color: var(--ink-muted);">Bulk add medicines</span>
        </summary>
        <div class="border-t px-4 lg:px-5 py-4" style="border-color: var(--border);">
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
    </details>

    <div class="rounded-xl border overflow-hidden" style="background: var(--bg-surface-elevated); border-color: var(--border);">
                <div class="px-3 lg:px-4 py-3 border-b flex flex-wrap items-center justify-between gap-3" style="border-color: var(--border); background: var(--bg-surface);">
                    <div class="flex items-center gap-2">
                        <label for="status-filter" class="text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);">Show</label>
                        <select id="status-filter" name="status" onchange="changeStatusFilter(this)"
                                class="rounded-lg border py-2 pl-3 pr-8 text-sm focus:outline-none focus:ring-2 transition"
                                style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                            <option value="active" @selected($status === 'active')>Active ({{ $activeCount }})</option>
                            <option value="all" @selected($status === 'all')>All ({{ $totalCount }})</option>
                            <option value="archived" @selected($status === 'archived')>Archived ({{ $archivedCount }})</option>
                        </select>
                    </div>

                    @if (! $medicines->isEmpty())
                    <div class="flex items-center gap-3">
                        <button id="select-all-btn" type="button" class="text-xs font-semibold hover:underline" style="color: var(--primary);">Select all</button>
                        <span class="h-4 w-px" style="background: var(--border);" aria-hidden="true"></span>
                        <div id="bulk-actions-bar" class="flex items-center gap-3 hidden">
                            <span class="text-sm" style="color: var(--ink-muted);"><span id="selected-count">0</span> selected</span>
                            @if ($status !== 'archived')
                            <button id="bulk-archive-btn" type="submit" form="bulk-form" onclick="setBulkAction(this)" data-action="archive"
                                    class="inline-flex items-center justify-center px-3 py-2 rounded-lg text-xs font-semibold text-white transition hover:opacity-90"
                                    style="background: var(--danger);">
                                Archive selected
                            </button>
                            @endif
                            @if ($status !== 'active')
                            <button id="bulk-restore-btn" type="submit" form="bulk-form" onclick="setBulkAction(this)" data-action="restore"
                                    class="inline-flex items-center justify-center px-3 py-2 rounded-lg text-xs font-semibold text-white transition hover:opacity-90"
                                    style="background: var(--primary);">
                                Restore selected
                            </button>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                <form id="bulk-form" action="{{ route('medicines.bulk-delete') }}" method="POST" onsubmit="return confirmBulkAction();">
                    @csrf
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead style="background: var(--teal-soft);">
                                <tr>
                                    <th class="w-10 px-2 py-2 lg:py-3 text-left" style="color: var(--ink-muted);"></th>
                                    <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium uppercase tracking-wide" style="color: var(--ink-muted);">Name</th>
                                    <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium uppercase tracking-wide hidden xl:table-cell" style="color: var(--ink-muted);">Form</th>
                                    @if ($status === 'all')
                                    <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium uppercase tracking-wide" style="color: var(--ink-muted);">Status</th>
                                    @elseif ($status === 'archived')
                                    <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium uppercase tracking-wide hidden md:table-cell" style="color: var(--ink-muted);">Archived</th>
                                    @endif
                                    <th class="px-3 lg:px-4 py-2 lg:py-3 text-right text-xs font-medium uppercase tracking-wide whitespace-nowrap" style="color: var(--ink-muted);">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--border)]">
                                @forelse ($medicines as $medicine)
                                    <tr class="transition-colors hover:bg-black/5">
                                        <td class="w-10 px-2 py-2 lg:py-3 text-center" style="color: var(--ink);">
                                            <input type="checkbox" name="ids[]" value="{{ $medicine->id }}" class="row-checkbox size-5 cursor-pointer" aria-label="Select {{ $medicine->name }}">
                                        </td>
                                        <td class="px-3 lg:px-4 py-2 lg:py-3" style="color: var(--ink);">{{ $medicine->name ?? '-' }}</td>
                                        <td class="px-3 lg:px-4 py-2 lg:py-3 hidden xl:table-cell" style="color: var(--ink-muted);">{{ $medicine->form ?? '-' }}</td>
                                        @if ($status === 'all')
                                        <td class="px-3 lg:px-4 py-2 lg:py-3 whitespace-nowrap">
                                            @if ($medicine->trashed())
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium" style="background: var(--bg-page); color: var(--ink-muted); border: 1px solid var(--border);">
                                                <i class="fa-solid fa-box-archive" aria-hidden="true"></i> Archived
                                            </span>
                                            @else
                                            <span class="inline-flex items-center gap-1.5 text-xs font-medium" style="color: var(--ink-muted);">
                                                <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Active
                                            </span>
                                            @endif
                                        </td>
                                        @elseif ($status === 'archived')
                                        <td class="px-3 lg:px-4 py-2 lg:py-3 hidden md:table-cell" style="color: var(--ink-muted);">{{ $medicine->deleted_at ? \Carbon\Carbon::parse($medicine->deleted_at)->format('M d, Y') : '-' }}</td>
                                        @endif
                                        <td class="px-3 lg:px-4 py-2 lg:py-3 text-right whitespace-nowrap">
                                            @if ($medicine->trashed())
                                                <form action="{{ route('medicines.restore', $medicine->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-sm font-medium hover:underline" style="color: var(--primary);">Restore</button>
                                                </form>
                                            @else
                                                <a href="{{ route('medicines.show', $medicine->id) }}" class="text-sm font-medium hover:underline" style="color: var(--primary);">View</a>
                                                <span class="mx-2" style="color: var(--ink-muted);">·</span>
                                                <a href="{{ route('medicines.edit', $medicine->id) }}" class="text-sm font-medium hover:underline" style="color: var(--primary);">Edit</a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $status === 'active' ? 4 : 5 }}" class="px-3 lg:px-4 py-12 text-center">
                                            <div class="flex justify-center mb-3"><i class="fa-solid fa-pills text-3xl" style="color: var(--ink-subtle);"></i></div>
                                            <p class="text-sm font-medium" style="color: var(--ink);">
                                                {{ $status === 'archived' ? 'No archived medicines' : ($status === 'all' ? 'No medicines found' : 'No medicines in inventory') }}
                                            </p>
                                            <p class="text-xs mt-1 mb-3" style="color: var(--ink-muted);">
                                                {{ $status === 'archived' ? 'Medicines you archive will appear here so they can be restored' : ($status === 'all' ? 'There are no medicines to show in this list' : 'Add your first medicine or import from CSV to get started') }}
                                            </p>
                                            @if ($status !== 'archived')
                                            <a href="{{ route('medicines.create') }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold text-white transition hover:opacity-90" style="background: var(--primary);"><i class="fa-solid fa-plus"></i> Add medicine</a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>

                <div class="border-t px-3 lg:px-4 py-3" style="border-color: var(--border);">
                    <x-pagination :paginator="$medicines" />
                </div>
            </div>
    </div>
    <script>
        (function(){
            function allRowCheckboxes(){
                return document.querySelectorAll('input.row-checkbox');
            }

            function updateSelectedCount(){
                const checkboxes = allRowCheckboxes();
                const checked = document.querySelectorAll('input.row-checkbox:checked').length;
                const bar = document.getElementById('bulk-actions-bar');
                const count = document.getElementById('selected-count');
                const selectAllBtn = document.getElementById('select-all-btn');
                if(bar) bar.classList.toggle('hidden', checked === 0);
                if(count) count.textContent = checked;
                if(selectAllBtn) selectAllBtn.textContent = (checkboxes.length > 0 && checked === checkboxes.length) ? 'Clear' : 'Select all';
            }

            document.addEventListener('DOMContentLoaded', function(){
                const selectAllBtn = document.getElementById('select-all-btn');
                if(selectAllBtn){
                    selectAllBtn.addEventListener('click', function(){
                        const checkboxes = allRowCheckboxes();
                        const checked = document.querySelectorAll('input.row-checkbox:checked').length;
                        const select = checked !== checkboxes.length;
                        checkboxes.forEach(cb => cb.checked = select);
                        updateSelectedCount();
                    });
                }

                allRowCheckboxes().forEach(cb => cb.addEventListener('change', updateSelectedCount));
            });

            window.changeStatusFilter = function(select){
                const url = new URL(window.location.href);
                url.searchParams.set('status', select.value);
                url.searchParams.delete('page');
                window.location.href = url.toString();
            };

            window.setBulkAction = function(button){
                const form = document.getElementById('bulk-form');
                form.dataset.action = button.dataset.action;
                form.action = button.dataset.action === 'restore'
                    ? '{{ route('medicines.bulk-restore') }}'
                    : '{{ route('medicines.bulk-delete') }}';
            };

            window.confirmBulkAction = function(){
                const form = document.getElementById('bulk-form');
                const checked = document.querySelectorAll('input.row-checkbox:checked').length;
                if(checked === 0){
                    Swal.fire({title: 'No selection', text: 'Please select at least one medicine.', icon: 'warning', confirmButtonColor: 'var(--primary)'});
                    return false;
                }
                const isRestore = form.dataset.action === 'restore';
                Swal.fire({
                    title: isRestore ? 'Restore selected medicines?' : 'Archive selected medicines?',
                    text: isRestore ? 'Restored medicines become available again for prescriptions.' : 'Archived medicines are hidden from the active list and can be restored anytime.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: isRestore ? 'var(--primary)' : 'var(--danger)',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: isRestore ? 'Restore' : 'Archive',
                    cancelButtonText: 'Cancel',
                }).then((result) => {
                    if (result.isConfirmed) form.submit();
                });
                return false;
            }
        })();
    </script>
@endsection

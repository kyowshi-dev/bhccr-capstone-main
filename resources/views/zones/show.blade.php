@extends('layouts.app')

@section('title', $zone->zone_number)

@section('content')
<div class="space-y-5 lg:space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <a href="{{ route('zones.index') }}" class="text-sm font-medium hover:underline mb-1 inline-block" style="color: var(--primary);">← Back to zones</a>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Zone {{ $zone->zone_number }}</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Zone details and management</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('zones.edit', $zone->id) }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition duration-200 hover:shadow-md" style="background: var(--primary);">
                Edit
            </a>
            <form action="{{ route('zones.destroy', $zone->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this zone? Make sure there are no households in this zone.')">
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
            <h2 class="font-display font-semibold text-lg mb-4" style="color: var(--ink);">Zone Information</h2>
            <dl class="space-y-3">
                <div>
                    <dt class="text-xs font-medium" style="color: var(--ink-muted);">Zone Number</dt>
                    <dd style="color: var(--ink);">{{ $zone->zone_number }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium" style="color: var(--ink-muted);">Assigned Worker</dt>
                    <dd style="color: var(--ink);">
                        @if ($zone->worker_name)
                            <span>{{ $zone->worker_name }}</span><br>
                            <span class="text-xs" style="color: var(--ink-muted);">{{ ucfirst($zone->worker_role ?? '') }}</span>
                        @else
                            <span style="color: var(--ink-muted);">Not assigned</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium" style="color: var(--ink-muted);">Created</dt>
                    <dd style="color: var(--ink);">{{ \Carbon\Carbon::parse($zone->created_at)->format('M d, Y \a\t H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium" style="color: var(--ink-muted);">Last Updated</dt>
                    <dd style="color: var(--ink);">{{ \Carbon\Carbon::parse($zone->updated_at)->format('M d, Y \a\t H:i') }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-xl border p-5 lg:p-6" style="background: var(--bg-surface); border-color: var(--border);">
            <h2 class="font-display font-semibold text-lg mb-4" style="color: var(--ink);">Statistics</h2>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm" style="color: var(--ink-muted);">Total households</span>
                    <span class="text-lg font-semibold" style="color: var(--primary);">{{ $zone->household_count ?? 0 }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm" style="color: var(--ink-muted);">Total patients</span>
                    <span class="text-lg font-semibold" style="color: var(--primary);">{{ $zone->patient_count ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    @if (($zone->household_count ?? 0) > 0)
        <div class="rounded-xl border overflow-hidden" style="background: var(--bg-surface-elevated); border-color: var(--border);">
            <div class="px-5 lg:px-6 py-3 lg:py-4" style="background: var(--teal-soft); border-bottom: 1px solid var(--border);">
                <h3 class="font-display font-semibold text-lg" style="color: var(--ink);">Households in this zone</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr style="background: var(--bg-surface);">
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted); border-bottom: 1px solid var(--border);">Family Head</th>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap hidden sm:table-cell" style="color: var(--ink-muted); border-bottom: 1px solid var(--border);">Contact</th>
                            <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap hidden md:table-cell" style="color: var(--ink-muted); border-bottom: 1px solid var(--border);">Members</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border)]">
                        @php
                            $households = DB::table('households')
                                ->leftJoin('patients as p', 'households.id', '=', 'p.household_id')
                                ->where('households.zone_id', $zone->id)
                                ->select(
                                    'households.id',
                                    'households.family_name_head',
                                    'households.contact_number',
                                    DB::raw('COUNT(p.id) as member_count')
                                )
                                ->groupBy('households.id', 'households.family_name_head', 'households.contact_number')
                                ->get();
                        @endphp
                        @forelse ($households as $household)
                            <tr class="transition-colors hover:bg-black/[0.02]">
                                <td class="px-3 lg:px-4 py-2 lg:py-3" style="color: var(--ink);">{{ $household->family_name_head }}</td>
                                <td class="px-3 lg:px-4 py-2 lg:py-3 hidden sm:table-cell" style="color: var(--ink-muted);">{{ $household->contact_number ?? '—' }}</td>
                                <td class="px-3 lg:px-4 py-2 lg:py-3 hidden md:table-cell" style="color: var(--ink-muted);">{{ $household->member_count ?? 0 }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-3 lg:px-4 py-6 text-center text-sm" style="color: var(--ink-muted);">No households in this zone.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

<?php

namespace App\Http\Controllers;

use App\DTOs\MaternalQueueDTO;
use App\Models\Consultation;
use App\Models\Pregnancy;
use App\Models\WatchlistEntry;
use App\Services\MaternalQueueAggregatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class MaternalQueueController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:maternal.view_queues')->only(['queuePartial']);
        $this->middleware('permission:maternal.manage_watchlist')->only(['addToWatchlist', 'removeFromWatchlist']);
        $this->middleware('permission:maternal.log_visit')->only(['linkPregnancy']);
    }

    public function queuePartial(Request $request, MaternalQueueAggregatorService $aggregator): View
    {
        $tab = $request->query('tab', 'all');
        $items = $aggregator->aggregate();

        if ($tab === 'all') {
            $grouped = $items->groupBy(fn (MaternalQueueDTO $dto) => $dto->patient_id)
                ->map(fn ($group) => MaternalQueueDTO::forGroupedCard($group))
                ->filter()
                ->values();

            return view('dashboards.partials.queue-cards', ['items' => $grouped, 'tab' => 'all']);
        }

        if ($tab === 'watchlist') {
            $filtered = $items->where('risk_level', 'high')->values();

            return view('dashboards.partials.queue-cards', ['items' => $filtered, 'tab' => $tab]);
        }

        $filtered = $items->where('program_type', $tab)->values();

        return view('dashboards.partials.queue-cards', ['items' => $filtered, 'tab' => $tab]);
    }

    public function addToWatchlist(Request $request, int $patientId): JsonResponse
    {
        $validated = $request->validate([
            'program_type' => ['required', 'string', 'in:prenatal,postnatal,fp,general'],
            'reason_code' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $entry = WatchlistEntry::create([
            'patient_id' => $patientId,
            'program_type' => $validated['program_type'],
            'reason_code' => $validated['reason_code'],
            'notes' => $validated['notes'] ?? null,
            'flagged_by' => user()->healthWorker->id ?? 1,
            'flagged_at' => now(),
        ]);

        Cache::forget('maternal_queue_aggregate');

        return response()->json(['message' => 'Added to watchlist.', 'entry_id' => $entry->id]);
    }

    public function removeFromWatchlist(int $entryId): JsonResponse
    {
        WatchlistEntry::where('id', $entryId)->update(['resolved_at' => now()]);

        Cache::forget('maternal_queue_aggregate');

        return response()->json(['message' => 'Removed from watchlist.']);
    }

    public function linkPregnancy(Request $request, Consultation $consultation): JsonResponse
    {
        $request->validate([
            'pregnancy_id' => ['required', 'integer', 'exists:pregnancies,id'],
        ]);

        $pregnancy = Pregnancy::where('id', $request->integer('pregnancy_id'))
            ->where('patient_id', $consultation->patient_id)
            ->where('status', Pregnancy::STATUS_ACTIVE)
            ->firstOrFail();

        $consultation->update(['pregnancy_id' => $pregnancy->id]);

        Cache::forget('maternal_queue_aggregate');

        return response()->json(['message' => 'Consultation linked to pregnancy.']);
    }
}

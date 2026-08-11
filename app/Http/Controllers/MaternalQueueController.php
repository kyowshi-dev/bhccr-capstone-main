<?php

namespace App\Http\Controllers;

use App\DTOs\MaternalQueueDTO;
use App\Http\Controllers\Concerns\ResolvesWorkerId;
use App\Http\Requests\AddWatchlistEntryRequest;
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
    use ResolvesWorkerId;

    public function queuePartial(Request $request, MaternalQueueAggregatorService $aggregator): View
    {
        $tab = $request->query('tab', 'all');
        $items = $aggregator->aggregate();

        $items = match ($tab) {
            'all' => $items->groupBy(fn (MaternalQueueDTO $dto) => $dto->patient_id)
                ->map(fn ($group) => MaternalQueueDTO::forGroupedCard($group))
                ->filter()
                ->values(),
            'watchlist' => $items->where('risk_level', 'high')->values(),
            default => $items->where('program_type', $tab)->values(),
        };

        return view('dashboards.partials.queue-cards', ['items' => $items, 'tab' => $tab]);
    }

    public function addToWatchlist(AddWatchlistEntryRequest $request, int $patientId): JsonResponse
    {
        $validated = $request->validated();

        $entry = WatchlistEntry::create([
            'patient_id' => $patientId,
            'program_type' => $validated['program_type'],
            'reason_code' => $validated['reason_code'],
            'notes' => $validated['notes'] ?? null,
            'flagged_by' => $this->currentWorkerId() ?? 1,
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

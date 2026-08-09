<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesWorkerId;
use App\Http\Requests\QuickMaternalActionRequest;
use App\Models\HealthWorker;
use App\Models\Patient;
use App\Services\MaternalQuickActionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class MaternalQuickActionController extends Controller
{
    use ResolvesWorkerId;

    public function __construct(private readonly MaternalQuickActionService $service) {}

    public function store(QuickMaternalActionRequest $request, Patient $patient): JsonResponse
    {
        $workerId = $this->currentWorkerId();

        $worker = HealthWorker::find($workerId);

        if (! $worker) {
            return response()->json(['message' => 'No health worker profile found.'], 403);
        }

        try {
            $result = $this->service->execute(
                $request->input('action'),
                $patient,
                $request->validated(),
                $worker->id,
            );

            return response()->json($result);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }
}

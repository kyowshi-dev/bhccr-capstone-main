<?php

namespace App\Observers;

use App\Models\FamilyPlanningClient;
use Illuminate\Support\Facades\Cache;

class FamilyPlanningClientObserver
{
    public function created(FamilyPlanningClient $client): void
    {
        $this->bust();
    }

    public function updated(FamilyPlanningClient $client): void
    {
        $this->bust();
    }

    public function deleted(FamilyPlanningClient $client): void
    {
        $this->bust();
    }

    private function bust(): void
    {
        Cache::forget('maternal_queue_aggregate');
        Cache::forget('maternal_queue_kpis');
    }
}

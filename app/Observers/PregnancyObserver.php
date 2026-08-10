<?php

namespace App\Observers;

use App\Models\Pregnancy;
use Illuminate\Support\Facades\Cache;

class PregnancyObserver
{
    public function created(Pregnancy $pregnancy): void
    {
        $this->bust();
    }

    public function updated(Pregnancy $pregnancy): void
    {
        $this->bust();
    }

    public function deleted(Pregnancy $pregnancy): void
    {
        $this->bust();
    }

    private function bust(): void
    {
        Cache::forget('maternal_queue_aggregate');
        Cache::forget('maternal_queue_kpis');
    }
}

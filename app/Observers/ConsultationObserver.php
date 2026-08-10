<?php

namespace App\Observers;

use App\Models\Consultation;
use Illuminate\Support\Facades\Cache;

class ConsultationObserver
{
    public function created(Consultation $consultation): void
    {
        $this->bust();
    }

    public function updated(Consultation $consultation): void
    {
        $this->bust();
    }

    public function deleted(Consultation $consultation): void
    {
        $this->bust();
    }

    private function bust(): void
    {
        Cache::forget('maternal_queue_aggregate');
        Cache::forget('maternal_queue_kpis');
    }
}

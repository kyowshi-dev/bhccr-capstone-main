<?php

namespace App\Observers;

use App\Models\PostnatalRecord;
use Illuminate\Support\Facades\Cache;

class PostnatalRecordObserver
{
    public function created(PostnatalRecord $record): void
    {
        $this->bust();
    }

    public function updated(PostnatalRecord $record): void
    {
        $this->bust();
    }

    public function deleted(PostnatalRecord $record): void
    {
        $this->bust();
    }

    private function bust(): void
    {
        Cache::forget('maternal_queue_aggregate');
        Cache::forget('maternal_queue_kpis');
    }
}

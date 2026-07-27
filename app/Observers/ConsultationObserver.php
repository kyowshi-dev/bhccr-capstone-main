<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Consultation;
use Illuminate\Support\Facades\Request;

class ConsultationObserver
{
    public function created(Consultation $consultation)
    {
        $this->log('created', 'consultations', $consultation->id, null, $consultation->toArray());
    }

    public function updated(Consultation $consultation)
    {
        $this->log('updated', 'consultations', $consultation->id, $consultation->getOriginal(), $consultation->toArray());
    }

    public function deleted(Consultation $consultation)
    {
        $this->log('deleted', 'consultations', $consultation->id, $consultation->toArray(), null);
    }

    protected function log($action, $table, $recordId, $oldValues, $newValues)
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'table_name' => $table,
            'record_id' => $recordId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
        ]);
    }
}
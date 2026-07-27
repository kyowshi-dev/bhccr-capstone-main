<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Patient;
use Illuminate\Support\Facades\Request;

class PatientObserver
{
    public function created(Patient $patient)
    {
        $this->log('created', 'patients', $patient->id, null, $patient->toArray());
    }

    public function updated(Patient $patient)
    {
        $this->log('updated', 'patients', $patient->id, $patient->getOriginal(), $patient->toArray());
    }

    public function deleted(Patient $patient)
    {
        $this->log('deleted', 'patients', $patient->id, $patient->toArray(), null);
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
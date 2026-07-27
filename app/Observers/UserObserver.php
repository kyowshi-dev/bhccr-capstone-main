<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Request;

class UserObserver
{
    public function created(User $user)
    {
        $this->log('created', 'users', $user->id, null, $user->toArray());
    }

    public function updated(User $user)
    {
        $this->log('updated', 'users', $user->id, $user->getOriginal(), $user->toArray());
    }

    public function deleted(User $user)
    {
        $this->log('deleted', 'users', $user->id, $user->toArray(), null);
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
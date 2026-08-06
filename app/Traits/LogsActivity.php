<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    /**
     * Columns to redact from audit log old/new values (e.g. password hashes).
     *
     * @var array<int, string>
     */
    protected $auditHiddenAttributes = ['password', 'remember_token'];

    protected static function bootLogsActivity()
    {
        foreach (['created', 'updated', 'deleted'] as $event) {
            static::$event(function ($model) use ($event) {

                // FIX: If it's an update, skip logging if nothing actually changed except the timestamp
                if ($event === 'updated') {
                    $dirty = $model->getDirty();
                    unset($dirty['updated_at']); // Ignore timestamp columns
                    if (empty($dirty)) {
                        return;
                    }
                }

                AuditLog::create([
                    'user_id' => Auth::id(), // Tracks who did it
                    'action' => $event,
                    'table_name' => $model->getTable(),
                    'record_id' => $model->id,
                    'old_values' => $event === 'created' ? null : $model->redactAuditValues($model->getOriginal()),
                    'new_values' => $event === 'deleted' ? null : $model->redactAuditValues($model->getAttributes()),
                    'ip_address' => request()->ip(),
                ]);
            });
        }
    }

    /**
     * Strip sensitive columns from values before persisting them to the audit log.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function redactAuditValues(array $values): array
    {
        foreach ($this->auditHiddenAttributes as $attribute) {
            unset($values[$attribute]);
        }

        return $values;
    }
}

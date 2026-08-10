<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

final class AuditLogService
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function log(string $action, string $table, Request $request, ?int $userId = null, ?int $recordId = null, ?array $oldValues = null, ?array $newValues = null): void
    {
        AuditLog::query()->create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'table_name' => $table,
            'record_id' => $recordId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }
}

<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string $table_name
 * @property int|null $record_id
 * @property array<array-key, mixed>|null $old_values
 * @property array<array-key, mixed>|null $new_values
 * @property string|null $ip_address
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read string $formatted_activity
 * @property-read User|null $user
 *
 * @method static Builder<static>|AuditLog newModelQuery()
 * @method static Builder<static>|AuditLog newQuery()
 * @method static Builder<static>|AuditLog query()
 * @method static Builder<static>|AuditLog whereAction($value)
 * @method static Builder<static>|AuditLog whereCreatedAt($value)
 * @method static Builder<static>|AuditLog whereId($value)
 * @method static Builder<static>|AuditLog whereIpAddress($value)
 * @method static Builder<static>|AuditLog whereNewValues($value)
 * @method static Builder<static>|AuditLog whereOldValues($value)
 * @method static Builder<static>|AuditLog whereRecordId($value)
 * @method static Builder<static>|AuditLog whereTableName($value)
 * @method static Builder<static>|AuditLog whereUserId($value)
 *
 * @mixin \Eloquent
 */
class AuditLog extends Model
{
    use Prunable;

    protected $table = 'audit_logs';

    /**
     * Get the query for pruning expired audit entries (retention policy).
     */
    public function prunable(): Builder
    {
        $retentionDays = (int) config('bhcis.audit_retention_days', 365);

        return static::query()->where('created_at', '<', now()->subDays($retentionDays));
    }

    protected $fillable = [
        'user_id',
        'action',
        'table_name',
        'record_id',
        'old_values',
        'new_values',
        'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function getFormattedActivityAttribute(): string
    {
        $time = Carbon::parse($this->created_at)->format('M d, Y H:i');
        $user = $this->user?->username ?: 'System';
        $action = ucfirst($this->action);
        $table = ucfirst(str_replace('_', ' ', $this->table_name));
        $record = $this->record_id !== null ? " #{$this->record_id}" : '';

        return "{$time} – {$user} {$action} {$table}{$record}";
    }

    public $timestamps = false;

    // Disable auto timestamps since we use created_at only
    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

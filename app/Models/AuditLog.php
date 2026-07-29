<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

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
        $time = \Carbon\Carbon::parse($this->created_at)->format('M d, Y H:i');
        $user = $this->user?->username ?: 'System';
        $action = ucfirst($this->action);
        $table = ucfirst(str_replace('_', ' ', $this->table_name));

        return "{$time} – {$user} {$action} {$table} #{$this->record_id}";
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
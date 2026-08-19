<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Prune audit logs past their retention window (config: bhcis.audit_retention_days).
Schedule::command('model:prune')->daily();

// Daily immunization due/overdue digest for staff with the immunizations permission.
Schedule::command('notifications:immunization-due')->dailyAt('08:00');

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE consultations MODIFY COLUMN status ENUM('triage', 'pending_doctor', 'in_progress', 'completed', 'referred') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE consultations MODIFY COLUMN status ENUM('triage', 'pending_doctor', 'completed', 'referred') NOT NULL");
        }
    }
};

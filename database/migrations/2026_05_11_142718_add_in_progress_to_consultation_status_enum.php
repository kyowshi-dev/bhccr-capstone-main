<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE consultations MODIFY COLUMN status ENUM('triage', 'pending_doctor', 'in_progress', 'completed', 'referred') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE consultations MODIFY COLUMN status ENUM('triage', 'pending_doctor', 'completed', 'referred') NOT NULL");
    }
};

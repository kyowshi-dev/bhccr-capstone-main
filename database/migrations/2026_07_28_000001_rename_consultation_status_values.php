<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('consultations')) {
            return;
        }

        // No explicit DB::transaction here: MySQL DDL (ALTER TABLE) implicitly
        // commits, which breaks nested transaction/savepoint handling.
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE consultations MODIFY COLUMN status ENUM('triage', 'pending_validation', 'pending_doctor', 'nurse_review', 'doctor_review', 'in_progress', 'completed', 'referred') NOT NULL");
        }

        DB::table('consultations')->where('status', 'pending_validation')->update(['status' => 'nurse_review']);
        DB::table('consultations')->where('status', 'pending_doctor')->update(['status' => 'doctor_review']);

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE consultations MODIFY COLUMN status ENUM('triage', 'nurse_review', 'doctor_review', 'in_progress', 'completed', 'referred') NOT NULL");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('consultations')) {
            return;
        }

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE consultations MODIFY COLUMN status ENUM('triage', 'pending_validation', 'pending_doctor', 'in_progress', 'completed', 'referred') NOT NULL");
        }
        DB::table('consultations')->where('status', 'nurse_review')->update(['status' => 'pending_validation']);
        DB::table('consultations')->where('status', 'doctor_review')->update(['status' => 'pending_doctor']);
    }
};

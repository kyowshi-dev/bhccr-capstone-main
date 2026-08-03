<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE consultations MODIFY COLUMN status ENUM('triage', 'nurse_review', 'doctor_review', 'in_progress', 'completed', 'referred') NOT NULL");
        }

        Schema::table('consultations', function (Blueprint $table) {
            $table->timestamp('nurse_validated_at')->nullable()->after('status');
            $table->foreignId('nurse_validated_by')->nullable()->after('nurse_validated_at')->constrained('health_workers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropForeign(['nurse_validated_by']);
            $table->dropColumn(['nurse_validated_at', 'nurse_validated_by']);
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE consultations MODIFY COLUMN status ENUM('triage', 'pending_doctor', 'in_progress', 'completed', 'referred') NOT NULL");
        }
    }
};

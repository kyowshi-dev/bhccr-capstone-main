<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->foreignId('attending_doctor_id')->nullable()->after('nurse_validated_by')->constrained('health_workers')->nullOnDelete();
        });

        // Backfill: the doctor who recorded the most recent diagnosis is the
        // de-facto attending provider for existing consultations.
        DB::statement(
            'UPDATE consultations
             SET attending_doctor_id = (
                 SELECT dr.diagnosed_by
                 FROM diagnosis_records dr
                 WHERE dr.consultation_id = consultations.id
                 ORDER BY dr.id DESC
                 LIMIT 1
             )
             WHERE EXISTS (
                 SELECT 1 FROM diagnosis_records dr2
                 WHERE dr2.consultation_id = consultations.id
             )'
        );
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropForeign(['attending_doctor_id']);
            $table->dropColumn('attending_doctor_id');
        });
    }
};

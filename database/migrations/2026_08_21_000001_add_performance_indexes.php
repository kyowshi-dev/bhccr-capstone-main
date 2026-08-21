<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Consultations are filtered by status (counts, queues, live-requests
        // polling) and ordered/aggregated by created_at / updated_at on nearly
        // every read path.
        Schema::table('consultations', function (Blueprint $table) {
            $table->index('status', 'consultations_status_index');
            $table->index('created_at', 'consultations_created_at_index');
            $table->index('updated_at', 'consultations_updated_at_index');
            $table->index('notified_at', 'consultations_notified_at_index');
        });

        // The dashboard's recent-activity feed orders audit_logs by created_at
        // with a limit; the table grows quickly (every model write is logged).
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('created_at', 'audit_logs_created_at_index');
        });

        // Immunization status computation filters a patient's records by
        // vaccine and real-dose flag repeatedly.
        Schema::table('immunization_records', function (Blueprint $table) {
            $table->index(['patient_id', 'vaccine_id', 'no_show'], 'immunization_records_patient_vaccine_noshow_index');
            $table->index(['date_given', 'no_show'], 'immunization_records_date_noshow_index');
        });

        // FHSIS report date-range filters (MCH/EPI/FP register and summaries).
        Schema::table('pregnancies', function (Blueprint $table) {
            $table->index('created_at', 'pregnancies_created_at_index');
        });

        Schema::table('prenatal_visits', function (Blueprint $table) {
            $table->index('visit_date', 'prenatal_visits_visit_date_index');
        });

        Schema::table('family_planning_clients', function (Blueprint $table) {
            $table->index('created_at', 'fp_clients_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropIndex('consultations_status_index');
            $table->dropIndex('consultations_created_at_index');
            $table->dropIndex('consultations_updated_at_index');
            $table->dropIndex('consultations_notified_at_index');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_created_at_index');
        });

        Schema::table('immunization_records', function (Blueprint $table) {
            $table->dropIndex('immunization_records_patient_vaccine_noshow_index');
            $table->dropIndex('immunization_records_date_noshow_index');
        });

        Schema::table('pregnancies', function (Blueprint $table) {
            $table->dropIndex('pregnancies_created_at_index');
        });

        Schema::table('prenatal_visits', function (Blueprint $table) {
            $table->dropIndex('prenatal_visits_visit_date_index');
        });

        Schema::table('family_planning_clients', function (Blueprint $table) {
            $table->dropIndex('fp_clients_created_at_index');
        });
    }
};

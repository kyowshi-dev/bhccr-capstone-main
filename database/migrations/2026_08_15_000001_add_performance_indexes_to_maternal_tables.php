<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pregnancies', function (Blueprint $table) {
            $table->index(['status', 'edc'], 'pregnancies_status_edc_index');
        });

        Schema::table('prenatal_visits', function (Blueprint $table) {
            $table->index('next_visit_date', 'prenatal_visits_next_visit_date_index');
            $table->index(['pregnancy_id', 'visit_date'], 'prenatal_visits_pregnancy_visit_date_index');
        });

        Schema::table('family_planning_clients', function (Blueprint $table) {
            $table->index(['is_active', 'schedule_next_visit'], 'fp_clients_active_schedule_index');
        });

        Schema::table('postnatal_records', function (Blueprint $table) {
            $table->index('delivery_date', 'postnatal_records_delivery_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('pregnancies', function (Blueprint $table) {
            $table->dropIndex('pregnancies_status_edc_index');
        });

        Schema::table('prenatal_visits', function (Blueprint $table) {
            $table->dropIndex('prenatal_visits_next_visit_date_index');
            $table->dropIndex('prenatal_visits_pregnancy_visit_date_index');
        });

        Schema::table('family_planning_clients', function (Blueprint $table) {
            $table->dropIndex('fp_clients_active_schedule_index');
        });

        Schema::table('postnatal_records', function (Blueprint $table) {
            $table->dropIndex('postnatal_records_delivery_date_index');
        });
    }
};

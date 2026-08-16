<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('diagnosis_records', 'custom_diagnosis_name')) {
            Schema::table('diagnosis_records', function (Blueprint $table) {
                $table->string('custom_diagnosis_name', 255)->nullable()->after('diagnosis_id');
            });
        }

        if (! Schema::hasColumn('prescriptions', 'custom_medicine_name')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->string('custom_medicine_name', 255)->nullable()->after('medicine_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('diagnosis_records', 'custom_diagnosis_name')) {
            Schema::table('diagnosis_records', function (Blueprint $table) {
                $table->dropColumn('custom_diagnosis_name');
            });
        }

        if (Schema::hasColumn('prescriptions', 'custom_medicine_name')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->dropColumn('custom_medicine_name');
            });
        }
    }
};

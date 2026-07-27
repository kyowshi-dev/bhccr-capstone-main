<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Fix the Complaint Issue
        Schema::table('consultations', function (Blueprint $table) {
            // Add a text column so we can save "Fever" without needing an ID lookup
            $table->text('complaint_text')->nullable()->after('chief_complaint_id');
        });

        // 2. Fix the Prescription Crash
        Schema::table('prescriptions', function (Blueprint $table) {
            // Make frequency nullable since your form doesn't ask for it
            $table->string('frequency')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn('complaint_text');
        });
    }
};

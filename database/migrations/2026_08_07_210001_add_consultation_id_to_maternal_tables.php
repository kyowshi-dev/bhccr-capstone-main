<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prenatal_visits', function (Blueprint $table) {
            $table->foreignId('consultation_id')->nullable()->after('pregnancy_id')->constrained('consultations')->nullOnDelete();
        });

        Schema::table('postnatal_records', function (Blueprint $table) {
            $table->foreignId('consultation_id')->nullable()->after('pregnancy_id')->constrained('consultations')->nullOnDelete();
        });

        Schema::table('family_planning_visits', function (Blueprint $table) {
            $table->foreignId('consultation_id')->nullable()->after('client_id')->constrained('consultations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('family_planning_visits', function (Blueprint $table) {
            $table->dropForeign(['consultation_id']);
            $table->dropColumn('consultation_id');
        });

        Schema::table('postnatal_records', function (Blueprint $table) {
            $table->dropForeign(['consultation_id']);
            $table->dropColumn('consultation_id');
        });

        Schema::table('prenatal_visits', function (Blueprint $table) {
            $table->dropForeign(['consultation_id']);
            $table->dropColumn('consultation_id');
        });
    }
};

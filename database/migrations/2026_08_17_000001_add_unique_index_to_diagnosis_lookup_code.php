<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnosis_lookup', function (Blueprint $table) {
            $table->dropIndex('diagnosis_lookup_diagnosis_code_index');
            $table->unique('diagnosis_code', 'diagnosis_lookup_diagnosis_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('diagnosis_lookup', function (Blueprint $table) {
            $table->dropUnique('diagnosis_lookup_diagnosis_code_unique');
            $table->index('diagnosis_code', 'diagnosis_lookup_diagnosis_code_index');
        });
    }
};

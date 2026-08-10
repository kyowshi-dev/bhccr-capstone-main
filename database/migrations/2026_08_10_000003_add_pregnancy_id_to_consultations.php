<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table): void {
            $table->foreignId('pregnancy_id')
                ->nullable()
                ->after('attending_doctor_id')
                ->constrained('pregnancies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table): void {
            $table->dropForeign(['pregnancy_id']);
            $table->dropColumn('pregnancy_id');
        });
    }
};

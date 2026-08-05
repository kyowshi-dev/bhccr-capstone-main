<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vaccine_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vaccine_id')->constrained('vaccines_lookup')->restrictOnDelete();
            $table->unsignedTinyInteger('dose_number');
            $table->unsignedInteger('min_age_days');
            $table->unsignedInteger('gap_days')->nullable();
            $table->boolean('requires_temp')->default(false);
            $table->timestamps();
            $table->unique(['vaccine_id', 'dose_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vaccine_schedules');
    }
};

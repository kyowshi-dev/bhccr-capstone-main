<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vaccines_lookup', function (Blueprint $table) {
            $table->id();
            $table->string('vaccine_code')->nullable()->unique();
            $table->string('vaccine_name');
            $table->string('description')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('immunization_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('vaccine_id')->constrained('vaccines_lookup')->restrictOnDelete();
            $table->unsignedTinyInteger('dose_number');
            $table->date('date_given');
            $table->foreignId('administered_by')->nullable()->constrained('health_workers')->nullOnDelete();
            $table->string('batch_number')->nullable();
            $table->date('next_due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('immunization_records');
        Schema::dropIfExists('vaccines_lookup');
    }
};

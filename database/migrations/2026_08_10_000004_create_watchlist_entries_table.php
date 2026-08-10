<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watchlist_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('program_type', 50)->comment('prenatal|postnatal|fp|general');
            $table->string('reason_code', 100);
            $table->text('notes')->nullable();
            $table->foreignId('flagged_by')->constrained('health_workers')->cascadeOnDelete();
            $table->timestamp('flagged_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();
            $table->index(['patient_id', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watchlist_entries');
    }
};

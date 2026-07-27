<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vitals', function (Blueprint $table) {
            $table->string('phase')->default('triage')->after('consultation_id');
            $table->foreignId('captured_by')->nullable()->after('phase')->constrained('health_workers')->nullOnDelete();
            $table->text('notes')->nullable()->after('bmi');
        });

        DB::table('vitals')
            ->whereNull('phase')
            ->update(['phase' => 'triage']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vitals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('captured_by');
            $table->dropColumn(['phase', 'notes']);
        });
    }
};

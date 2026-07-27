<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('health_workers', 'position') && ! Schema::hasColumn('health_workers', 'role')) {
            Schema::table('health_workers', function (Blueprint $table) {
                $table->renameColumn('position', 'role');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('health_workers', 'role') && ! Schema::hasColumn('health_workers', 'position')) {
            Schema::table('health_workers', function (Blueprint $table) {
                $table->renameColumn('role', 'position');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table): void {
            if (! Schema::hasColumn('prescriptions', 'route')) {
                $table->string('route', 50)->nullable()->after('dosage');
            }
            if (! Schema::hasColumn('prescriptions', 'instructions')) {
                $table->string('instructions', 255)->nullable()->after('duration');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table): void {
            if (Schema::hasColumn('prescriptions', 'route')) {
                $table->dropColumn('route');
            }
            if (Schema::hasColumn('prescriptions', 'instructions')) {
                $table->dropColumn('instructions');
            }
        });
    }
};

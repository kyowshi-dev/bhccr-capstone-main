<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('immunization_records', function (Blueprint $table) {
            $table->decimal('child_weight_kg', 5, 2)->nullable()->after('temp_recorded');
            $table->decimal('child_height_cm', 5, 2)->nullable()->after('child_weight_kg');
        });
    }

    public function down(): void
    {
        Schema::table('immunization_records', function (Blueprint $table) {
            $table->dropColumn(['child_weight_kg', 'child_height_cm']);
        });
    }
};

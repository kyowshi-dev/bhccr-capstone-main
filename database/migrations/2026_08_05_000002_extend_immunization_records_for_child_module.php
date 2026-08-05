<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('immunization_records', function (Blueprint $table) {
            $table->decimal('temp_recorded', 4, 2)->nullable()->after('date_given');
            $table->boolean('no_show')->default(false)->after('notes');
            $table->timestamp('no_show_at')->nullable()->after('no_show');
        });
    }

    public function down(): void
    {
        Schema::table('immunization_records', function (Blueprint $table) {
            $table->dropColumn(['temp_recorded', 'no_show', 'no_show_at']);
        });
    }
};

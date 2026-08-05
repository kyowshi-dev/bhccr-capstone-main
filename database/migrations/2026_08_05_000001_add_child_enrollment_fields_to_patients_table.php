<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->decimal('birth_weight', 5, 2)->nullable()->after('date_of_birth');
            $table->string('guardian_name', 255)->nullable()->after('mother_name');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['birth_weight', 'guardian_name']);
        });
    }
};

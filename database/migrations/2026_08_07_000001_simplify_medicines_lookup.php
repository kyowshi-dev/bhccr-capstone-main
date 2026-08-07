<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicines_lookup', function (Blueprint $table) {
            $table->dropColumn([
                'generic_name',
                'strength',
                'manufacturer',
                'expiration_date',
                'is_active',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('medicines_lookup', function (Blueprint $table) {
            $table->string('generic_name')->nullable()->after('name');
            $table->string('strength')->nullable()->after('generic_name');
            $table->string('manufacturer')->nullable()->after('form');
            $table->date('expiration_date')->nullable()->after('manufacturer');
            $table->boolean('is_active')->default(true)->after('expiration_date');
        });
    }
};

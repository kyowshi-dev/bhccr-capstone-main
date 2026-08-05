<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vaccines_lookup', function (Blueprint $table) {
            $table->string('group_key', 50)->nullable()->index()->after('category');
            $table->unsignedInteger('start_after_days')->nullable()->after('group_key');
            $table->unsignedInteger('complete_before_days')->nullable()->after('start_after_days');
        });
    }

    public function down(): void
    {
        Schema::table('vaccines_lookup', function (Blueprint $table) {
            $table->dropIndex(['group_key']);
            $table->dropColumn(['group_key', 'start_after_days', 'complete_before_days']);
        });
    }
};

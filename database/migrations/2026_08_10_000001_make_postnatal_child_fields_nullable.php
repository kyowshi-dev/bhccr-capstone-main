<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('postnatal_records', function (Blueprint $table) {
            $table->string('child_last_name')->nullable()->change();
            $table->string('child_first_name')->nullable()->change();
            $table->enum('child_sex', ['M', 'F'])->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('postnatal_records', function (Blueprint $table) {
            $table->string('child_last_name')->nullable(false)->change();
            $table->string('child_first_name')->nullable(false)->change();
            $table->enum('child_sex', ['M', 'F'])->nullable(false)->change();
        });
    }
};

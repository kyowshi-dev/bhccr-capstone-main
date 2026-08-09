<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pregnancies', function (Blueprint $table) {
            $table->unsignedInteger('gravidity')->nullable()->default(null)->change();
            $table->unsignedInteger('parity')->nullable()->default(null)->change();
            $table->unsignedInteger('term')->nullable()->default(null)->change();
            $table->unsignedInteger('preterm')->nullable()->default(null)->change();
            $table->unsignedInteger('livebirth')->nullable()->default(null)->change();
            $table->unsignedInteger('abortion')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('pregnancies', function (Blueprint $table) {
            $table->unsignedInteger('gravidity')->nullable(false)->default(0)->change();
            $table->unsignedInteger('parity')->nullable(false)->default(0)->change();
            $table->unsignedInteger('term')->nullable(false)->default(0)->change();
            $table->unsignedInteger('preterm')->nullable(false)->default(0)->change();
            $table->unsignedInteger('livebirth')->nullable(false)->default(0)->change();
            $table->unsignedInteger('abortion')->nullable(false)->default(0)->change();
        });
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('father_name', 255)->nullable()->after('guardian_name');
            $table->foreignId('mother_id')->nullable()->after('father_name')
                ->constrained('patients')->nullOnDelete();
            $table->index('mother_id');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['mother_id']);
            $table->dropConstrainedForeignId('mother_id');
            $table->dropColumn('father_name');
        });
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->boolean('is_immunization_enrolled')->default(false)->after('has_nhts');
            $table->index('is_immunization_enrolled');
        });

        DB::table('patients')
            ->leftJoin('immunization_records', function ($join) {
                $join->on('patients.id', '=', 'immunization_records.patient_id')
                    ->where('immunization_records.no_show', false);
            })
            ->where(function ($q) {
                $q->whereNotNull('immunization_records.id')
                    ->orWhere('patients.date_of_birth', '>', now()->subYears(18)->toDateString());
            })
            ->update(['patients.is_immunization_enrolled' => true]);
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['is_immunization_enrolled']);
            $table->dropColumn('is_immunization_enrolled');
        });
    }
};

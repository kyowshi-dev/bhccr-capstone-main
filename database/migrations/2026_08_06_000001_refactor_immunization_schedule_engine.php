<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vaccines_lookup', function (Blueprint $table) {
            $table->unsignedInteger('repeat_months')->nullable()->after('complete_before_days');
        });

        DB::table('vaccines_lookup')->where('vaccine_code', 'FLU')->update(['repeat_months' => 12]);

        // next_due_date was a denormalized cache of date_given + gap_days that
        // went stale on schedule reseeds; due dates are now derived from the
        // schedule engine on read (ChildImmunizationService::nextDoseDate).
        Schema::table('immunization_records', function (Blueprint $table) {
            $table->dropColumn('next_due_date');
        });
    }

    public function down(): void
    {
        Schema::table('immunization_records', function (Blueprint $table) {
            $table->date('next_due_date')->nullable();
        });

        DB::table('vaccines_lookup')->where('vaccine_code', 'FLU')->update(['repeat_months' => null]);

        Schema::table('vaccines_lookup', function (Blueprint $table) {
            $table->dropColumn('repeat_months');
        });
    }
};

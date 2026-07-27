<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->string('mode_of_transaction')->after('nature_of_visit');
            $table->string('referred_from')->nullable()->after('mode_of_transaction');
            $table->boolean('refer_to_higher_facility')->default(false)->after('referred_from');
            $table->string('referred_to')->nullable()->after('refer_to_higher_facility');
            $table->text('referral_reason')->nullable()->after('referred_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn(['mode_of_transaction', 'referred_from', 'refer_to_higher_facility', 'referred_to', 'referral_reason']);
        });
    }
};

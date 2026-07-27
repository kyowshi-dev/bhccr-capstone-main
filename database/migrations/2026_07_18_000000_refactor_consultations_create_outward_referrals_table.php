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
        if (Schema::hasColumn('consultations', 'refer_to_higher_facility')) {
            Schema::table('consultations', function (Blueprint $table) {
                $table->dropColumn(['refer_to_higher_facility', 'referred_to', 'referral_reason']);
            });
        }

        Schema::create('outward_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained('consultations')->cascadeOnDelete();
            $table->string('destination_facility');
            $table->text('pertinent_history');
            $table->text('actions_taken')->nullable();
            $table->text('specific_details')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->unique('consultation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outward_referrals');

        Schema::table('consultations', function (Blueprint $table) {
            $table->boolean('refer_to_higher_facility')->default(false)->after('referred_from');
            $table->string('referred_to')->nullable()->after('refer_to_higher_facility');
            $table->text('referral_reason')->nullable()->after('referred_to');
        });
    }
};

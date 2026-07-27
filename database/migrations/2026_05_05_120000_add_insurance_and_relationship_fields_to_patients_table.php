<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('mother_name');
            $table->string('spouse_name');
            $table->enum('family_relationship', ['Father', 'Son', 'Mother', 'Daughter', 'Others']);
            $table->string('residential_address');
            $table->enum('is_philhealth_member', ['y', 'n'])->default('n');
            $table->enum('status_type', ['Member', 'Dependent'])->nullable();
            $table->string('philhealth_no', 20)->nullable();
            $table->enum('membership_category', ['FE - Private', 'FE - Government', 'IE', 'Others'])->nullable();
            $table->enum('is_pcb_member', ['y', 'n'])->default('n');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'mother_name',
                'spouse_name',
                'family_relationship',
                'residential_address',
                'is_philhealth_member',
                'status_type',
                'philhealth_no',
                'membership_category',
                'is_pcb_member',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add the zones permission if it doesn't exist
        $exists = DB::table('permissions')
            ->where('name', 'zones')
            ->exists();

        if (! $exists) {
            DB::table('permissions')->insert([
                'name' => 'zones',
                'description' => 'Manage geographic zones and assign health workers',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('permissions')
            ->where('name', 'zones')
            ->delete();
    }
};

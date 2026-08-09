<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('vaccines_lookup')->where('vaccine_code', 'PNEUMONIA')->update(['category' => 'Adult']);
        DB::table('vaccines_lookup')->where('vaccine_code', 'FLU')->update(['category' => 'Adult']);
    }

    public function down(): void
    {
        DB::table('vaccines_lookup')->where('vaccine_code', 'PNEUMONIA')->update(['category' => 'Child']);
        DB::table('vaccines_lookup')->where('vaccine_code', 'FLU')->update(['category' => 'Both']);
    }
};

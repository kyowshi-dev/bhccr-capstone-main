<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $zones = [];
        for ($i = 1; $i <= 8; $i++) {
            $zones[] = [
                'id' => $i,
                'zone_number' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('zones')->insert($zones);
    }
}
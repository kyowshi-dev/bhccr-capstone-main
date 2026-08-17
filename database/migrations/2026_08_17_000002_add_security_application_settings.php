<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $settings = [
            // Account lockout (defaults: lock after 5 failed attempts for 15 minutes)
            ['key' => 'login_max_attempts', 'value' => '5'],
            ['key' => 'lockout_duration_minutes', 'value' => '15'],
            // Password policy (defaults: min 8 chars, require uppercase + number)
            ['key' => 'password_min_length', 'value' => '8'],
            ['key' => 'password_require_uppercase', 'value' => '1'],
            ['key' => 'password_require_number', 'value' => '1'],
            ['key' => 'password_require_symbol', 'value' => '0'],
        ];

        $now = now();

        foreach ($settings as $setting) {
            DB::table('application_settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('application_settings')
            ->whereIn('key', [
                'login_max_attempts',
                'lockout_duration_minutes',
                'password_min_length',
                'password_require_uppercase',
                'password_require_number',
                'password_require_symbol',
            ])
            ->delete();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Feature-level permissions that are not tied to a single resource module.
     *
     * @var list<string>
     */
    private array $permissions = [
        'print_handouts',
        'dashboard_handouts',
    ];

    public function up(): void
    {
        $now = now();

        foreach ($this->permissions as $name) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                [
                    'name' => $name,
                    'description' => $name === 'print_handouts'
                        ? 'Print consultation Rx and diagnosis handouts'
                        : 'View completed handouts panel on dashboards',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('name', $this->permissions)->delete();
    }
};

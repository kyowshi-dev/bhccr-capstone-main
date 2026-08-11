<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Permissions consolidated into a single `dashboard_handouts` gate.
     *
     * @var list<string>
     */
    private array $legacyHandoutPermissions = [
        'dashboard_handouts_bhw',
        'dashboard_handouts_clinical',
        'dashboard_handouts_midwife',
        'dashboard_handouts_admin',
    ];

    /**
     * Maternal sub-permissions merged into the parent `maternal` module gate.
     *
     * @var list<string>
     */
    private array $legacyMaternalPermissions = [
        'maternal.view_queues',
        'maternal.log_visit',
        'maternal.manage_watchlist',
    ];

    public function up(): void
    {
        DB::transaction(function () {
            $now = now();

            DB::table('permissions')->updateOrInsert(
                ['name' => 'dashboard_handouts'],
                [
                    'name' => 'dashboard_handouts',
                    'description' => 'View completed handouts panel on dashboards',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $mergedId = DB::table('permissions')->where('name', 'dashboard_handouts')->value('id');

            if ($mergedId !== null) {
                $this->mergeHandoutPermissions((int) $mergedId);
            }

            $this->dropMaternalSubPermissions();
        });
    }

    private function mergeHandoutPermissions(int $mergedId): void
    {
        $legacyIds = DB::table('permissions')
            ->whereIn('name', $this->legacyHandoutPermissions)
            ->pluck('id');

        if ($legacyIds->isEmpty()) {
            return;
        }

        $roleIds = DB::table('role_permissions')
            ->whereIn('permission_id', $legacyIds)
            ->distinct()
            ->pluck('role_id');

        foreach ($roleIds as $roleId) {
            $legacyRows = DB::table('role_permissions')
                ->where('role_id', $roleId)
                ->whereIn('permission_id', $legacyIds)
                ->orderBy('id')
                ->get();

            $alreadyMerged = DB::table('role_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $mergedId)
                ->exists();

            if ($alreadyMerged) {
                DB::table('role_permissions')->whereIn('id', $legacyRows->pluck('id'))->delete();

                continue;
            }

            // Move the first legacy row onto the merged permission...
            $firstRow = $legacyRows->shift();
            DB::table('role_permissions')
                ->where('id', $firstRow->id)
                ->update(['permission_id' => $mergedId, 'updated_at' => now()]);

            // ...and drop the remaining duplicates for this role.
            if ($legacyRows->isNotEmpty()) {
                DB::table('role_permissions')->whereIn('id', $legacyRows->pluck('id'))->delete();
            }
        }

        DB::table('permissions')->whereIn('id', $legacyIds)->delete();
    }

    private function dropMaternalSubPermissions(): void
    {
        $maternalIds = DB::table('permissions')
            ->whereIn('name', $this->legacyMaternalPermissions)
            ->pluck('id');

        if ($maternalIds->isEmpty()) {
            return;
        }

        DB::table('role_permissions')->whereIn('permission_id', $maternalIds)->delete();
        DB::table('permissions')->whereIn('id', $maternalIds)->delete();
    }

    public function down(): void
    {
        $now = now();

        foreach (array_merge($this->legacyHandoutPermissions, $this->legacyMaternalPermissions) as $name) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                ['name' => $name, 'description' => 'Legacy permission: '.$name, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }
};

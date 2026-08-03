<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnosis_records', function (Blueprint $table) {
            $table->string('custom_diagnosis_code', 20)->nullable()->after('diagnosis_id');
            $table->string('custom_diagnosis_name', 255)->nullable()->after('custom_diagnosis_code');
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->string('custom_medicine_name', 255)->nullable()->after('medicine_id');
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->makeForeignKeyNullableSqlite('diagnosis_records', 'diagnosis_id');
            $this->makeForeignKeyNullableSqlite('prescriptions', 'medicine_id');
        } else {
            Schema::table('diagnosis_records', function (Blueprint $table) {
                $table->dropForeign(['diagnosis_id']);
            });
            Schema::table('diagnosis_records', function (Blueprint $table) {
                $table->unsignedBigInteger('diagnosis_id')->nullable()->change();
                $table->foreign('diagnosis_id')->references('id')->on('diagnosis_lookup');
            });

            Schema::table('prescriptions', function (Blueprint $table) {
                $table->dropForeign(['medicine_id']);
            });
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->unsignedBigInteger('medicine_id')->nullable()->change();
                $table->foreign('medicine_id')->references('id')->on('medicines_lookup');
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DELETE FROM diagnosis_records WHERE diagnosis_id IS NULL');
            DB::statement('DELETE FROM prescriptions WHERE medicine_id IS NULL');
            $this->makeForeignKeyRequiredSqlite('diagnosis_records', 'diagnosis_id', 'diagnosis_lookup');
            $this->makeForeignKeyRequiredSqlite('prescriptions', 'medicine_id', 'medicines_lookup');
        } else {
            DB::table('diagnosis_records')->whereNull('diagnosis_id')->delete();
            DB::table('prescriptions')->whereNull('medicine_id')->delete();

            Schema::table('diagnosis_records', function (Blueprint $table) {
                $table->dropForeign(['diagnosis_id']);
            });
            Schema::table('diagnosis_records', function (Blueprint $table) {
                $table->unsignedBigInteger('diagnosis_id')->nullable(false)->change();
                $table->foreign('diagnosis_id')->references('id')->on('diagnosis_lookup');
            });

            Schema::table('prescriptions', function (Blueprint $table) {
                $table->dropForeign(['medicine_id']);
            });
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->unsignedBigInteger('medicine_id')->nullable(false)->change();
                $table->foreign('medicine_id')->references('id')->on('medicines_lookup');
            });
        }

        Schema::table('diagnosis_records', function (Blueprint $table) {
            $table->dropColumn(['custom_diagnosis_code', 'custom_diagnosis_name']);
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn('custom_medicine_name');
        });
    }

    private function makeForeignKeyNullableSqlite(string $table, string $column): void
    {
        $this->rebuildSqliteTable($table, function (array $columns, array $foreignKeys) use ($column) {
            $columns[$column] = [...$columns[$column], 'notnull' => 0];

            return [$columns, $foreignKeys];
        });
    }

    private function makeForeignKeyRequiredSqlite(string $table, string $column, string $referencedTable): void
    {
        $this->rebuildSqliteTable($table, function (array $columns, array $foreignKeys) use ($column, $referencedTable) {
            $columns[$column] = [...$columns[$column], 'notnull' => 1];

            $hasForeignKey = collect($foreignKeys)
                ->contains(fn (array $group) => $group[0]['from'] === $column);

            if (! $hasForeignKey) {
                $foreignKeys[] = [[
                    'from' => $column,
                    'table' => $referencedTable,
                    'to' => 'id',
                    'on_update' => 'NO ACTION',
                    'on_delete' => 'NO ACTION',
                ]];
            }

            return [$columns, $foreignKeys];
        });
    }

    private function rebuildSqliteTable(string $table, callable $transform): void
    {
        DB::statement('PRAGMA foreign_keys=off');

        $rows = DB::select("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", [$table]);
        if (empty($rows)) {
            DB::statement('PRAGMA foreign_keys=on');

            return;
        }

        $columns = collect(DB::select('PRAGMA table_info("'.$table.'")'))
            ->mapWithKeys(fn ($column) => [
                $column->name => [
                    'type' => $column->type,
                    'notnull' => (int) $column->notnull,
                    'dflt_value' => $column->dflt_value,
                    'pk' => (int) $column->pk,
                ],
            ])
            ->all();

        $foreignKeys = collect(DB::select('PRAGMA foreign_key_list("'.$table.'")'))
            ->groupBy('id')
            ->map(fn ($group) => $group->map(fn ($fk) => [
                'from' => $fk->from,
                'table' => $fk->table,
                'to' => $fk->to,
                'on_update' => $fk->on_update,
                'on_delete' => $fk->on_delete,
            ])->values()->all())
            ->values()
            ->all();

        [$columns, $foreignKeys] = $transform($columns, $foreignKeys);

        $definitions = collect($columns)
            ->map(function (array $column, string $name): string {
                $definition = '"'.$name.'" '.$column['type']
                    .($column['notnull'] ? ' NOT NULL' : ' NULL');

                if ($column['dflt_value'] !== null && strtoupper((string) $column['dflt_value']) !== 'NULL') {
                    $definition .= ' DEFAULT '.$column['dflt_value'];
                }

                if ($column['pk']) {
                    $definition .= ' PRIMARY KEY AUTOINCREMENT';
                }

                return $definition;
            })
            ->merge(
                collect($foreignKeys)->map(function (array $group): string {
                    $columns = implode(', ', array_map(fn (array $fk) => '"'.$fk['from'].'"', $group));
                    $references = implode(', ', array_map(fn (array $fk) => '"'.$fk['table'].'"("'.$fk['to'].'")', $group));

                    return sprintf(
                        'FOREIGN KEY (%s) REFERENCES %s ON UPDATE %s ON DELETE %s',
                        $columns,
                        $references,
                        $group[0]['on_update'] ?? 'NO ACTION',
                        $group[0]['on_delete'] ?? 'NO ACTION'
                    );
                })
            )
            ->all();

        $tempTable = $table.'_tmp_custom_entries';

        DB::statement('DROP TABLE IF EXISTS "'.$tempTable.'"');
        DB::statement('CREATE TABLE "'.$tempTable.'" ('.implode(', ', $definitions).')');
        DB::statement('INSERT INTO "'.$tempTable.'" SELECT * FROM "'.$table.'"');
        DB::statement('DROP TABLE "'.$table.'"');
        DB::statement('ALTER TABLE "'.$tempTable.'" RENAME TO "'.$table.'"');

        DB::statement('PRAGMA foreign_keys=on');
    }
};

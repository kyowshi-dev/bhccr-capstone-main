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
            $this->makeForeignKeyNullableSqlite('diagnosis_records', 'diagnosis_id', 'diagnosis_lookup');
            $this->makeForeignKeyNullableSqlite('prescriptions', 'medicine_id', 'medicines_lookup');
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

    private function makeForeignKeyNullableSqlite(string $table, string $column, string $referencedTable): void
    {
        DB::statement('PRAGMA foreign_keys=off');

        $rows = DB::select("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", [$table]);
        if (empty($rows)) {
            DB::statement('PRAGMA foreign_keys=on');

            return;
        }

        $createSql = (string) $rows[0]->sql;
        $createSql = preg_replace(
            '/\s*foreign key\s*\(\s*["`]?'.$column.'["`]?\s*\)\s*references\s*["`]?'.$referencedTable.'["`]?\s*\(\s*["`]?id["`]?\s*\)/i',
            '',
            $createSql
        );
        $createSql = preg_replace('/,\s*,/', ',', $createSql);
        $createSql = preg_replace('/\(\s*,/', '(', $createSql);
        $createSql = preg_replace('/,\s*\)/', ')', $createSql);

        $tempTable = $table.'_tmp_custom_entries';
        $createSql = preg_replace('/create table\s+["`]?'.$table.'["`]?/i', 'CREATE TABLE "'.$tempTable.'"', $createSql, 1);
        $createSql = preg_replace('/\b'.$column.'\b(?!\s+nullable)/i', $column.' INTEGER NULL', $createSql, 1);

        DB::statement($createSql);
        DB::statement('INSERT INTO "'.$tempTable.'" SELECT * FROM "'.$table.'"');
        DB::statement('DROP TABLE "'.$table.'"');
        DB::statement('ALTER TABLE "'.$tempTable.'" RENAME TO "'.$table.'"');

        DB::statement('PRAGMA foreign_keys=on');
    }

    private function makeForeignKeyRequiredSqlite(string $table, string $column, string $referencedTable): void
    {
        DB::statement('PRAGMA foreign_keys=off');

        $rows = DB::select("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", [$table]);
        if (empty($rows)) {
            DB::statement('PRAGMA foreign_keys=on');

            return;
        }

        $createSql = (string) $rows[0]->sql;
        $createSql = preg_replace('/\b'.$column.'\s+INTEGER\s+NULL\b/i', $column.' INTEGER NOT NULL', $createSql, 1);

        $tempTable = $table.'_tmp_custom_entries';
        $createSql = preg_replace('/create table\s+["`]?'.$table.'["`]?/i', 'CREATE TABLE "'.$tempTable.'"', $createSql, 1);

        if (! preg_match('/foreign key\s*\(\s*["`]?'.$column.'["`]?\s*\)/i', $createSql)) {
            $createSql = preg_replace(
                '/\)\s*$/',
                ', foreign key("'.$column.'") references "'.$referencedTable.'"(id))',
                $createSql
            );
        }

        DB::statement($createSql);
        DB::statement('INSERT INTO "'.$tempTable.'" SELECT * FROM "'.$table.'"');
        DB::statement('DROP TABLE "'.$table.'"');
        DB::statement('ALTER TABLE "'.$tempTable.'" RENAME TO "'.$table.'"');

        DB::statement('PRAGMA foreign_keys=on');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow system-level audit events (login, logout, backup export/import)
     * that are not tied to a single domain record.
     */
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('record_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // SQLite rebuilds the table to restore NOT NULL; any system-level audit
        // events (auth, backups) with a NULL record_id would violate it.
        DB::table('audit_logs')->whereNull('record_id')->delete();

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('record_id')->nullable(false)->change();
        });
    }
};

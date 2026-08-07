<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Encrypt patients.philhealth_no at rest (RA 10173 / HIPAA safeguard).
     *
     * The column is widened to TEXT because Laravel's `encrypted` cast stores a
     * base64 payload far longer than the original VARCHAR(20). Existing plaintext
     * values are encrypted in place via the query builder so the model cast does
     * not try to decrypt them mid-migration.
     */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->text('philhealth_no')->nullable()->change();
        });

        // Normalize empty strings to NULL so the `encrypted` cast treats them as absent.
        DB::table('patients')->where('philhealth_no', '')->update(['philhealth_no' => null]);

        DB::table('patients')
            ->whereNotNull('philhealth_no')
            ->orderBy('id')
            ->chunkById(100, function ($patients) {
                foreach ($patients as $patient) {
                    DB::table('patients')
                        ->where('id', $patient->id)
                        ->update(['philhealth_no' => encrypt($patient->philhealth_no)]);
                }
            });
    }

    public function down(): void
    {
        DB::table('patients')
            ->whereNotNull('philhealth_no')
            ->orderBy('id')
            ->chunkById(100, function ($patients) {
                foreach ($patients as $patient) {
                    try {
                        DB::table('patients')
                            ->where('id', $patient->id)
                            ->update(['philhealth_no' => decrypt($patient->philhealth_no)]);
                    } catch (Throwable) {
                        // Already plaintext; leave untouched.
                    }
                }
            });

        Schema::table('patients', function (Blueprint $table) {
            $table->string('philhealth_no', 20)->nullable()->change();
        });
    }
};

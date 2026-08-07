<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Repair philhealth_no values left in plaintext by an outdated database import.
     *
     * The App\Models\Patient model uses the resilient EncryptedString cast, so a
     * stale dump no longer crashes patient pages — but any plaintext national IDs
     * must still be encrypted at rest (RA 10173 / HIPAA). This migration is
     * idempotent: rows that already decrypt are skipped, rows that do not are
     * encrypted in place via the query builder (bypassing the model cast).
     */
    public function up(): void
    {
        if (! Schema::hasTable('patients') || ! Schema::hasColumn('patients', 'philhealth_no')) {
            return;
        }

        DB::table('patients')->where('philhealth_no', '')->update(['philhealth_no' => null]);

        DB::table('patients')
            ->whereNotNull('philhealth_no')
            ->orderBy('id')
            ->chunkById(100, function ($patients) {
                foreach ($patients as $patient) {
                    try {
                        decrypt($patient->philhealth_no);
                    } catch (DecryptException) {
                        DB::table('patients')
                            ->where('id', $patient->id)
                            ->update(['philhealth_no' => encrypt($patient->philhealth_no)]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Encryption is a one-way at-rest safeguard; no reversible action.
    }
};

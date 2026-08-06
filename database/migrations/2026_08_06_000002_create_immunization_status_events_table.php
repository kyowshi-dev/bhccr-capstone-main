<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('immunization_status_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('vaccine_id')->constrained('vaccines_lookup')->cascadeOnDelete();
            $table->unsignedTinyInteger('dose_number')->nullable();
            $table->string('event_type'); // missed | attended | cleared
            $table->date('event_date');
            $table->string('note', 500)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'vaccine_id', 'event_type'], 'status_events_vaccine_type_idx');
        });

        // Backfill: legacy no-show placeholder rows become MISSED events so the
        // append-only event history carries the record; the legacy rows are left
        // untouched (never deleted) but are no longer written going forward.
        DB::table('immunization_records')
            ->where('no_show', true)
            ->orderBy('date_given')
            ->get()
            ->each(function (object $record) {
                DB::table('immunization_status_events')->insert([
                    'patient_id' => $record->patient_id,
                    'vaccine_id' => $record->vaccine_id,
                    'dose_number' => $record->dose_number,
                    'event_type' => 'missed',
                    'event_date' => $record->no_show_at ?? $record->date_given,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('immunization_status_events');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add Unique Constraints
        Schema::table('zones', function (Blueprint $table) {
            $table->unique('zone_number', 'unique_zone_number');
        });

        Schema::table('medicines_lookup', function (Blueprint $table) {
            $table->unique('medicine_name', 'unique_medicine_name');
        });
        Schema::table('patients', function (Blueprint $table) {
        // Copilot prompt: create a composite unique index for first_name, last_name, middle_name, and date_of_birth named 'unique_patient_record
            $table->unique(['first_name', 'last_name', 'middle_name', 'date_of_birth'], 'unique_patient_record');
    });

        // 2. Add Triggers (Using raw SQL)
        DB::unprepared('
            CREATE TRIGGER auto_capitalize_insert
            BEFORE INSERT ON patients
            FOR EACH ROW
            BEGIN
                IF NEW.first_name IS NOT NULL THEN
                    SET NEW.first_name = CONCAT(UPPER(SUBSTRING(NEW.first_name, 1, 1)), LOWER(SUBSTRING(NEW.first_name, 2)));
                END IF;
                IF NEW.last_name IS NOT NULL THEN
                    SET NEW.last_name = CONCAT(UPPER(SUBSTRING(NEW.last_name, 1, 1)), LOWER(SUBSTRING(NEW.last_name, 2)));
                END IF;
            END;
        ');

        DB::unprepared('
            CREATE TRIGGER auto_capitalize_update
            BEFORE UPDATE ON patients
            FOR EACH ROW
            BEGIN
                IF NEW.first_name IS NOT NULL THEN
                    SET NEW.first_name = CONCAT(UPPER(SUBSTRING(NEW.first_name, 1, 1)), LOWER(SUBSTRING(NEW.first_name, 2)));
                END IF;
                IF NEW.last_name IS NOT NULL THEN
                    SET NEW.last_name = CONCAT(UPPER(SUBSTRING(NEW.last_name, 1, 1)), LOWER(SUBSTRING(NEW.last_name, 2)));
                END IF;
            END;
        ');
    }

    public function down(): void
    {
        // 1. Remove Unique Constraints
        Schema::table('zones', function (Blueprint $table) {
            $table->dropUnique('unique_zone_number');
        });

        Schema::table('medicines_lookup', function (Blueprint $table) {
            $table->dropUnique('unique_medicine_name');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropUnique('unique_patient_record');
        });

        // 2. Remove Triggers
        DB::unprepared('DROP TRIGGER IF EXISTS auto_capitalize_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS auto_capitalize_update');
    }
};

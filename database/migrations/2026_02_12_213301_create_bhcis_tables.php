<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Roles & Users
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->string('role_name')->unique();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password');
            $table->foreignId('role_id')->constrained('user_roles')->restrictOnDelete();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        // 2. Health Workers (Staff Profile)
        Schema::create('health_workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('position');
            $table->string('contact_number')->nullable();
            $table->timestamps();
        });

        // 3. Lookups (For Incremental Search)
        Schema::create('diagnosis_lookup', function (Blueprint $table) {
            $table->id();
            $table->string('diagnosis_code')->index();
            $table->string('diagnosis_name');
            $table->string('category')->nullable();
        });

        Schema::create('medicines_lookup', function (Blueprint $table) {
            $table->id();
            $table->string('medicine_name');
            $table->text('description')->nullable();
            $table->date('expiration_date')->nullable();
        });

        Schema::create('complaint_lookup', function (Blueprint $table) {
            $table->id();
            $table->string('complaint');
        });

        Schema::create('facilities_lookup', function (Blueprint $table) {
            $table->id();
            $table->string('facility_name');
            $table->string('facility_type');
        });

        // 4. Patients & Geographic Zones
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string('zone_number');
            $table->foreignId('assigned_worker_id')->nullable()->constrained('health_workers');
            $table->timestamps();
        });

        Schema::create('households', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('zones');
            $table->string('family_name_head');
            $table->string('contact_number')->nullable();
            $table->timestamps();
        });

        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained('households')->restrictOnDelete();

            // Basic Info
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('suffix')->nullable();
            $table->enum('sex', ['Male', 'Female']);
            $table->date('date_of_birth');
            $table->string('birth_place')->nullable();
            $table->string('blood_type')->nullable();

            // Socio-Economic
            $table->string('civil_status');
            $table->string('educational_attainment')->nullable();
            $table->string('employment_status')->nullable();
            $table->boolean('has_4ps')->default(false);
            $table->boolean('has_nhts')->default(false);

            $table->timestamps();

            // Search Optimization (MySQL/MariaDB only)
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->fullText(['last_name', 'first_name']);
            }
        });

        // 5. Medical Records
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('worker_id')->constrained('health_workers');
            $table->enum('status', ['triage', 'pending_doctor', 'completed', 'referred']);
            $table->boolean('is_locked')->default(false);
            $table->foreignId('chief_complaint_id')->nullable()->constrained('complaint_lookup');
            $table->string('nature_of_visit')->nullable();
            $table->timestamps();
        });

        Schema::create('vitals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained('consultations')->cascadeOnDelete();
            $table->string('bp_systolic')->nullable();
            $table->string('bp_diastolic')->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('height_cm', 5, 2)->nullable();
            $table->decimal('temperature_c', 4, 2)->nullable();
            $table->decimal('bmi', 4, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('diagnosis_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained('consultations');
            $table->foreignId('diagnosis_id')->constrained('diagnosis_lookup');
            $table->text('remarks')->nullable();
            $table->foreignId('diagnosed_by')->constrained('health_workers');
            $table->timestamps();
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained('consultations');
            $table->foreignId('medicine_id')->constrained('medicines_lookup');
            $table->string('dosage');
            $table->string('frequency');
            $table->string('duration');
            $table->timestamps();
        });

        // 6. Audit Logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('action');
            $table->string('table_name');
            $table->unsignedBigInteger('record_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Raw SQL for FullText Search Indexes (MySQL/MariaDB only)
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE diagnosis_lookup ADD FULLTEXT(diagnosis_name, diagnosis_code)');
            DB::statement('ALTER TABLE medicines_lookup ADD FULLTEXT(medicine_name)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('diagnosis_records');
        Schema::dropIfExists('vitals');
        Schema::dropIfExists('consultations');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('households');
        Schema::dropIfExists('zones');
        Schema::dropIfExists('facilities_lookup');
        Schema::dropIfExists('complaint_lookup');
        Schema::dropIfExists('medicines_lookup');
        Schema::dropIfExists('diagnosis_lookup');
        Schema::dropIfExists('health_workers');
        Schema::dropIfExists('users');
        Schema::dropIfExists('user_roles');
    }
};

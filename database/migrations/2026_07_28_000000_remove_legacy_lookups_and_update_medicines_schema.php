<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('lab_requests');
        Schema::dropIfExists('password_reset_requests');

        Schema::table('consultations', function (Blueprint $table) {
            if (Schema::hasColumn('consultations', 'chief_complaint_id')) {
                $table->dropForeign(['chief_complaint_id']);
                $table->dropColumn('chief_complaint_id');
            }
        });

        Schema::dropIfExists('complaint_lookup');
        Schema::dropIfExists('facilities_lookup');

        Schema::table('medicines_lookup', function (Blueprint $table) {
            if (Schema::hasColumn('medicines_lookup', 'category')) {
                $table->dropColumn('category');
            }
            if (Schema::hasColumn('medicines_lookup', 'description')) {
                $table->dropColumn('description');
            }
            if (! Schema::hasColumn('medicines_lookup', 'name')) {
                $table->string('name')->nullable()->after('medicine_name');
            }
            if (! Schema::hasColumn('medicines_lookup', 'generic_name')) {
                $table->string('generic_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('medicines_lookup', 'strength')) {
                $table->string('strength')->nullable()->after('generic_name');
            }
            if (! Schema::hasColumn('medicines_lookup', 'form')) {
                $table->string('form')->nullable()->after('strength');
            }
            if (! Schema::hasColumn('medicines_lookup', 'manufacturer')) {
                $table->string('manufacturer')->nullable()->after('form');
            }
            if (! Schema::hasColumn('medicines_lookup', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('expiration_date');
            }
        });

        if (Schema::hasColumn('medicines_lookup', 'medicine_name')) {
            DB::table('medicines_lookup')->whereNull('name')->update(['name' => DB::raw('medicine_name')]);
            Schema::table('medicines_lookup', function (Blueprint $table) {
                $table->dropColumn('medicine_name');
            });
        }

        Schema::table('diagnosis_records', function (Blueprint $table) {
            if (Schema::hasColumn('diagnosis_records', 'custom_diagnosis_code')) {
                $table->dropColumn('custom_diagnosis_code');
            }
            if (Schema::hasColumn('diagnosis_records', 'custom_diagnosis_name')) {
                $table->dropColumn('custom_diagnosis_name');
            }
        });

        Schema::table('immunization_records', function (Blueprint $table) {
            if (Schema::hasColumn('immunization_records', 'batch_number')) {
                $table->dropColumn('batch_number');
            }
        });
    }

    public function down(): void
    {
        Schema::create('complaint_lookup', function (Blueprint $table) {
            $table->id();
            $table->string('complaint');
        });

        Schema::create('facilities_lookup', function (Blueprint $table) {
            $table->id();
            $table->string('facility_name');
            $table->string('facility_type');
        });

        Schema::create('password_reset_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('username_requested');
            $table->enum('status', ['pending', 'completed', 'rejected'])->default('pending');
            $table->text('admin_note')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('lab_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained('consultations')->nullOnDelete();
            $table->string('lab_test_name');
            $table->text('lab_test_description')->nullable();
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->date('requested_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->text('results')->nullable();
            $table->text('notes')->nullable();
            $table->string('requester_first_name')->nullable();
            $table->string('requester_last_name')->nullable();
            $table->timestamps();
        });

        Schema::table('consultations', function (Blueprint $table) {
            if (! Schema::hasColumn('consultations', 'chief_complaint_id')) {
                $table->foreignId('chief_complaint_id')->nullable()->constrained('complaint_lookup')->after('is_locked');
            }
        });

        if (! Schema::hasColumn('medicines_lookup', 'medicine_name')) {
            Schema::table('medicines_lookup', function (Blueprint $table) {
                $table->string('medicine_name')->nullable()->after('id');
            });
        }

        if (Schema::hasColumn('medicines_lookup', 'name')) {
            DB::table('medicines_lookup')->whereNull('medicine_name')->update(['medicine_name' => DB::raw('name')]);
            Schema::table('medicines_lookup', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }

        Schema::table('medicines_lookup', function (Blueprint $table) {
            if (! Schema::hasColumn('medicines_lookup', 'description')) {
                $table->text('description')->nullable()->after('medicine_name');
            }
            if (! Schema::hasColumn('medicines_lookup', 'category')) {
                $table->string('category')->nullable()->after('medicine_name');
            }
            if (Schema::hasColumn('medicines_lookup', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('medicines_lookup', 'manufacturer')) {
                $table->dropColumn('manufacturer');
            }
            if (Schema::hasColumn('medicines_lookup', 'form')) {
                $table->dropColumn('form');
            }
            if (Schema::hasColumn('medicines_lookup', 'strength')) {
                $table->dropColumn('strength');
            }
            if (Schema::hasColumn('medicines_lookup', 'generic_name')) {
                $table->dropColumn('generic_name');
            }
        });

        Schema::table('diagnosis_records', function (Blueprint $table) {
            if (! Schema::hasColumn('diagnosis_records', 'custom_diagnosis_code')) {
                $table->string('custom_diagnosis_code', 20)->nullable()->after('diagnosis_id');
            }
            if (! Schema::hasColumn('diagnosis_records', 'custom_diagnosis_name')) {
                $table->string('custom_diagnosis_name', 255)->nullable()->after('custom_diagnosis_code');
            }
        });

        Schema::table('immunization_records', function (Blueprint $table) {
            if (! Schema::hasColumn('immunization_records', 'batch_number')) {
                $table->string('batch_number')->nullable()->after('administered_by');
            }
        });
    }
};

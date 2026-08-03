<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\ImmunizationController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ZoneController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- PUBLIC ROUTES (No login required) ---
Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::get('/login', [AuthController::class, 'showLogin']);
Route::post('/login', [AuthController::class, 'processLogin'])
    ->middleware('throttle:5,1')  // 5 attempts per minute
    ->name('login.process');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/password/forgot', [AuthController::class, 'showForgotPassword'])->name('password.forgot');
Route::post('/password/forgot', [AuthController::class, 'submitForgotPassword'])
    ->middleware('throttle:3,1')  // 3 attempts per minute
    ->name('password.forgot.submit');
Route::get('/password/forgot/verify', [AuthController::class, 'showForgotVerify'])->name('password.forgot.verify');
Route::post('/password/forgot/verify', [AuthController::class, 'submitForgotVerify'])
    ->middleware('throttle:5,1')  // 5 attempts per minute
    ->name('password.forgot.verify.submit');

// --- PROTECTED ROUTES (Only for logged-in users) ---
Route::middleware('auth')->group(function () {

    // 1. DASHBOARD
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 2. SEARCH API (For AJAX/autocomplete)
    Route::get('/search/patients', [SearchController::class, 'patients'])->name('search.patients');
    Route::get('/search/diagnoses', [SearchController::class, 'diagnoses'])->name('search.diagnoses');
    Route::get('/search/medicines', [SearchController::class, 'medicines'])->name('search.medicines');
    Route::get('/search/households', [SearchController::class, 'households'])->name('search.households');

    // 3. PATIENT MANAGEMENT
    Route::get('/households', [HouseholdController::class, 'index'])
        ->name('households.index');
    Route::get('/households/create', [HouseholdController::class, 'create'])
        ->name('households.create');
    Route::post('/households', [HouseholdController::class, 'store'])
        ->name('households.store');
    Route::get('/households/{id}/edit', [HouseholdController::class, 'edit'])
        ->name('households.edit');
    Route::put('/households/{id}', [HouseholdController::class, 'update'])
        ->name('households.update');
    Route::post('/households/export/csv', [HouseholdController::class, 'exportCSV'])
        ->name('households.export.csv');
    Route::post('/households/export/pdf', [HouseholdController::class, 'exportPDF'])
        ->name('households.export.pdf');
    Route::post('/households/bulk-update-zone', [HouseholdController::class, 'updateZone'])
        ->name('households.update-zone');

    // 3a. Patients
    Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');

    // 3b. Create Patient (Order matters: This must be BEFORE {id})
    Route::get('/patients/create', [PatientController::class, 'create'])->name('patients.create');
    Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');

    // 3c. Show Patient Profile (Wildcard catches IDs like 1, 2, 100)
    Route::get('/patients/{id}', [PatientController::class, 'show'])->name('patients.show');

    // 4. CONSULTATION MODULE
    // Consultation History (list) – must be before /consultations/{id}
    Route::get('/consultations', [ConsultationController::class, 'index'])->name('consultations.index');
    Route::get('/consultations/live-requests', [ConsultationController::class, 'liveRequests'])->name('consultations.live-requests');
    Route::get('/referrals', [ReferralController::class, 'index'])->name('referrals.index');
    Route::get('/referrals/{id}/print', [ReferralController::class, 'print'])->name('referrals.print');
    Route::patch('/referrals/{id}/status', [ReferralController::class, 'updateStatus'])->name('referrals.update-status');

    // Triage / New Admission
    Route::get('/patients/{id}/consultations/create', [ConsultationController::class, 'create'])->name('consultations.create');
    Route::post('/patients/{id}/consultations', [ConsultationController::class, 'store'])->name('consultations.store');

    // Quick Edit for Consultations
    Route::get('/consultations/{id}/edit', [ConsultationController::class, 'edit'])->name('consultations.edit');
    Route::put('/consultations/{id}', [ConsultationController::class, 'update'])->name('consultations.update');

    // Doctor's Workspace (View specific consultation)
    Route::get('/consultations/{id}', [ConsultationController::class, 'show'])
        ->name('consultations.show');

    // Doctor Actions (Diagnosis & Rx)
    Route::post('/consultations/{id}/diagnosis', [ConsultationController::class, 'addDiagnosis'])
        ->name('consultations.diagnosis');
    Route::post('/consultations/{id}/finalize', [ConsultationController::class, 'finalizeConsultation'])
        ->name('consultations.finalize');
    Route::post('/consultations/{id}/refer', [ConsultationController::class, 'refer'])
        ->name('consultations.refer');
    Route::get('/consultations/{id}/referral-context', [ConsultationController::class, 'referralContext'])
        ->name('consultations.referral-context');
    Route::post('/consultations/{id}/acknowledge-intake', [ConsultationController::class, 'acknowledgeIntake'])
        ->name('consultations.acknowledge-intake');
    Route::delete('/consultations/{id}', [ConsultationController::class, 'cancelIntake'])
        ->name('consultations.cancel');
    Route::get('/consultations/{id}/handout', [ConsultationController::class, 'printHandout'])
        ->name('consultations.handout');
    Route::get('/consultations/{id}/handout/pdf', [ConsultationController::class, 'downloadHandoutPdf'])
        ->name('consultations.handout.pdf');
    Route::post('/consultations/{id}/vitals/retake', [ConsultationController::class, 'retakeVitals'])
        ->name('consultations.vitals.retake');
    Route::put('/consultations/{consultationId}/vitals/{vitalId}', [ConsultationController::class, 'updateVitalVersion'])
        ->name('consultations.vitals.update');
    Route::delete('/consultations/{consultationId}/vitals/{vitalId}', [ConsultationController::class, 'deleteVitalVersion'])
        ->name('consultations.vitals.delete');
    Route::post('/consultations/{id}/prescription', [ConsultationController::class, 'addPrescription'])
        ->name('consultations.prescription');
    Route::delete('/consultations/{consultationId}/diagnoses/{diagnosisId}', [ConsultationController::class, 'deleteDiagnosis'])
        ->name('consultations.diagnosis.delete');
    Route::delete('/consultations/{consultationId}/prescriptions/{prescriptionId}', [ConsultationController::class, 'deletePrescription'])
        ->name('consultations.prescription.delete');

    // 5. IMMUNIZATION
    Route::get('/immunizations', [ImmunizationController::class, 'index'])->name('immunizations.index');
    Route::get('/patients/{id}/immunizations', [ImmunizationController::class, 'forPatient'])->name('immunizations.patient');
    Route::post('/immunizations', [ImmunizationController::class, 'store'])->name('immunizations.store');
    Route::post('/patients/{id}/immunizations/administer', [ImmunizationController::class, 'administer'])
        ->name('immunizations.administer');

    // 6. REPORTS (FHSIS)
    Route::get('/reports', [ReportController::class, 'index'])
        ->name('reports.index');
    Route::get('/reports/morbidity', [ReportController::class, 'morbidity'])
        ->name('reports.morbidity');
    Route::get('/reports/morbidity/download', [ReportController::class, 'downloadMorbidityPdf'])
        ->name('reports.morbidity.download');

    // 8. USER MANAGEMENT
    Route::get('/users', [UserManagementController::class, 'index'])
        ->name('users.index');
    Route::get('/users/create', [UserManagementController::class, 'create'])
        ->name('users.create');
    Route::post('/users', [UserManagementController::class, 'store'])
        ->name('users.store');
    Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])
        ->name('users.edit');
    Route::put('/users/{user}', [UserManagementController::class, 'update'])
        ->name('users.update');
    Route::post('/users/{user}/disable', [UserManagementController::class, 'disable'])
        ->name('users.disable');
    Route::post('/users/{user}/enable', [UserManagementController::class, 'enable'])
        ->name('users.enable');
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])
        ->name('users.destroy');

    // 9. User Permissions
    Route::get('/users/{user}/permissions', [UserManagementController::class, 'editPermissions'])
        ->name('users.permissions.edit');
    Route::get('/users/{user}/permissions-data', [UserManagementController::class, 'getPermissionsData'])
        ->name('users.permissions.data');
    Route::put('/users/{user}/permissions', [UserManagementController::class, 'updatePermissions'])
        ->name('users.permissions.update');

    // 10. ZONE MANAGEMENT
    Route::get('/zones', [ZoneController::class, 'index'])
        ->name('zones.index');
    Route::get('/zones/create', [ZoneController::class, 'create'])
        ->name('zones.create');
    Route::post('/zones', [ZoneController::class, 'store'])
        ->name('zones.store');
    Route::get('/zones/{id}', [ZoneController::class, 'show'])
        ->name('zones.show');
    Route::get('/zones/{id}/edit', [ZoneController::class, 'edit'])
        ->name('zones.edit');
    Route::put('/zones/{id}', [ZoneController::class, 'update'])
        ->name('zones.update');
    Route::delete('/zones/{id}', [ZoneController::class, 'destroy'])
        ->name('zones.destroy');

    // 11. MEDICINE MANAGEMENT
    Route::get('/medicines', [MedicineController::class, 'index'])
        ->name('medicines.index');
    Route::get('/medicines/create', [MedicineController::class, 'create'])
        ->name('medicines.create');
    Route::post('/medicines', [MedicineController::class, 'store'])
        ->name('medicines.store');
    Route::post('/medicines/import', [MedicineController::class, 'import'])
        ->name('medicines.import');
    Route::post('/medicines/bulk-delete', [MedicineController::class, 'bulkDestroy'])
        ->name('medicines.bulk-delete');
    Route::get('/medicines/{id}', [MedicineController::class, 'show'])
        ->name('medicines.show');
    Route::get('/medicines/{id}/edit', [MedicineController::class, 'edit'])
        ->name('medicines.edit');
    Route::put('/medicines/{id}', [MedicineController::class, 'update'])
        ->name('medicines.update');
    Route::delete('/medicines/{id}', [MedicineController::class, 'destroy'])
        ->name('medicines.destroy');

    // 12. SETTINGS
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/account', [SettingsController::class, 'account'])->name('settings.account');
    Route::post('/settings/account', [SettingsController::class, 'updateAccount'])->name('settings.account.update');
    Route::get('/settings/backups', [SettingsController::class, 'backups'])
        ->name('settings.backups');
    Route::post('/settings/backups/export', [SettingsController::class, 'exportBackup'])
        ->name('settings.backups.export');
    Route::post('/settings/backups/import', [SettingsController::class, 'importBackup'])
        ->name('settings.backups.import');

    // 13. PROFILE MANAGEMENT
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/settings', [ProfileController::class, 'settings'])->name('profile.settings');
    Route::put('/profile/settings', [ProfileController::class, 'updateSettings'])->name('profile.settings.update');
    Route::get('/session/status', [ProfileController::class, 'sessionStatus'])->name('session.status');

    // 14. NOTIFICATIONS
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/mark-read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::post('/notifications/destroy-all', [NotificationController::class, 'destroyAll'])->name('notifications.destroy-all');

    // 15. MISC

}); // <--- End of Auth Group

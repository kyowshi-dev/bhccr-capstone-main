<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FamilyPlanningController;
use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\ImmunizationController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PostnatalController;
use App\Http\Controllers\PrenatalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleManagementController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ZoneController;
use App\Http\Middleware\ReadOnlySession;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

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
    // PII-bearing endpoints are permission-gated; all searches are rate-limited.
    Route::get('/search/patients', [SearchController::class, 'patients'])
        ->middleware('permission:patients', 'throttle:60,1')
        ->name('search.patients');
    Route::get('/search/households', [SearchController::class, 'households'])
        ->middleware('permission:household', 'throttle:60,1')
        ->name('search.households');
    Route::get('/search/diagnoses', [SearchController::class, 'diagnoses'])
        ->middleware('permission:consultations', 'throttle:60,1')
        ->name('search.diagnoses');
    Route::get('/search/medicines', [SearchController::class, 'medicines'])
        ->middleware('permission:consultations', 'throttle:60,1')
        ->name('search.medicines');

    // 3. PATIENT MANAGEMENT
    Route::get('/households', [HouseholdController::class, 'index'])
        ->middleware('permission:household')
        ->name('households.index');
    Route::get('/households/create', [HouseholdController::class, 'create'])
        ->middleware('permission:household')
        ->name('households.create');
    Route::post('/households', [HouseholdController::class, 'store'])
        ->middleware('permission:household')
        ->name('households.store');
    Route::get('/households/{id}/edit', [HouseholdController::class, 'edit'])
        ->middleware('permission:household')
        ->name('households.edit');
    Route::put('/households/{id}', [HouseholdController::class, 'update'])
        ->middleware('permission:household')
        ->name('households.update');
    Route::post('/households/export/csv', [HouseholdController::class, 'exportCSV'])
        ->middleware('permission:household')
        ->name('households.export.csv');
    Route::post('/households/export/pdf', [HouseholdController::class, 'exportPDF'])
        ->middleware('permission:household')
        ->name('households.export.pdf');
    Route::post('/households/bulk-update-zone', [HouseholdController::class, 'updateZone'])
        ->middleware('permission:household')
        ->name('households.update-zone');

    // 3a. Patients
    Route::get('/patients', [PatientController::class, 'index'])
        ->middleware('permission:patients')
        ->name('patients.index');

    // 3b. Create Patient (Order matters: This must be BEFORE {id})
    Route::get('/patients/create', [PatientController::class, 'create'])
        ->middleware('permission:patients')
        ->name('patients.create');
    Route::post('/patients', [PatientController::class, 'store'])
        ->middleware('permission:patients')
        ->name('patients.store');

    // 3c. Show Patient Profile (Wildcard catches IDs like 1, 2, 100)
    Route::get('/patients/{id}', [PatientController::class, 'show'])
        ->middleware('permission:patients')
        ->name('patients.show');

    // 4. CONSULTATION MODULE
    // Consultation History (list) – must be before /consultations/{id}
    Route::get('/consultations', [ConsultationController::class, 'index'])
        ->middleware('permission:consultations')
        ->name('consultations.index');
    Route::get('/referrals', [ReferralController::class, 'index'])
        ->middleware('permission:consultations')
        ->name('referrals.index');
    Route::get('/referrals/{id}/print', [ReferralController::class, 'print'])
        ->middleware('permission:print_handouts')
        ->name('referrals.print');
    Route::patch('/referrals/{id}/status', [ReferralController::class, 'updateStatus'])
        ->middleware('permission:consultations')
        ->name('referrals.update-status');

    // Triage / New Admission
    Route::get('/patients/{patient}/consultations/create', [ConsultationController::class, 'create'])
        ->middleware('permission:consultations')
        ->name('consultations.create');
    Route::post('/patients/{patient}/consultations', [ConsultationController::class, 'store'])
        ->middleware('permission:consultations')
        ->name('consultations.store');

    // Quick Edit for Consultations
    Route::get('/consultations/{consultation}/edit', [ConsultationController::class, 'edit'])
        ->middleware('permission:consultations')
        ->name('consultations.edit');
    Route::put('/consultations/{consultation}', [ConsultationController::class, 'update'])
        ->middleware('permission:consultations')
        ->name('consultations.update');

    // Doctor's Workspace (View specific consultation)
    Route::get('/consultations/{consultation}', [ConsultationController::class, 'show'])
        ->whereNumber('consultation')
        ->middleware('permission:consultations')
        ->name('consultations.show');

    // Doctor Actions (Diagnosis & Rx)
    Route::post('/consultations/{consultation}/diagnosis', [ConsultationController::class, 'addDiagnosis'])
        ->middleware('permission:consultations')
        ->name('consultations.diagnosis');
    Route::post('/consultations/{consultation}/finalize', [ConsultationController::class, 'finalizeConsultation'])
        ->middleware('permission:consultations')
        ->name('consultations.finalize');
    Route::post('/consultations/{consultation}/refer', [ConsultationController::class, 'refer'])
        ->middleware('permission:consultations')
        ->name('consultations.refer');
    Route::get('/consultations/{consultation}/referral-context', [ConsultationController::class, 'referralContext'])
        ->middleware('permission:consultations')
        ->name('consultations.referral-context');
    Route::post('/consultations/{consultation}/acknowledge-intake', [ConsultationController::class, 'acknowledgeIntake'])
        ->middleware('permission:consultations')
        ->name('consultations.acknowledge-intake');
    Route::delete('/consultations/{consultation}', [ConsultationController::class, 'cancelIntake'])
        ->middleware('permission:consultations')
        ->name('consultations.cancel');
    Route::get('/consultations/{consultation}/handout', [ConsultationController::class, 'printHandout'])
        ->middleware('permission:print_handouts')
        ->name('consultations.handout');
    Route::get('/consultations/{consultation}/handout/pdf', [ConsultationController::class, 'downloadHandoutPdf'])
        ->middleware('permission:print_handouts')
        ->name('consultations.handout.pdf');
    Route::post('/consultations/{consultation}/vitals/retake', [ConsultationController::class, 'retakeVitals'])
        ->middleware('permission:consultations')
        ->name('consultations.vitals.retake');
    Route::put('/consultations/{consultation}/vitals/{vitalId}', [ConsultationController::class, 'updateVitalVersion'])
        ->middleware('permission:consultations')
        ->name('consultations.vitals.update');
    Route::delete('/consultations/{consultation}/vitals/{vitalId}', [ConsultationController::class, 'deleteVitalVersion'])
        ->middleware('permission:consultations')
        ->name('consultations.vitals.delete');
    Route::post('/consultations/{consultation}/prescription', [ConsultationController::class, 'addPrescription'])
        ->middleware('permission:consultations')
        ->name('consultations.prescription');
    Route::delete('/consultations/{consultation}/diagnoses/{diagnosisId}', [ConsultationController::class, 'deleteDiagnosis'])
        ->middleware('permission:consultations')
        ->name('consultations.diagnosis.delete');
    Route::delete('/consultations/{consultation}/prescriptions/{prescriptionId}', [ConsultationController::class, 'deletePrescription'])
        ->middleware('permission:consultations')
        ->name('consultations.prescription.delete');

    // 5. IMMUNIZATION
    Route::get('/immunizations', [ImmunizationController::class, 'index'])
        ->middleware('permission:immunizations')
        ->name('immunizations.index');
    Route::get('/immunizations/checkin/{patient}', [ImmunizationController::class, 'checkin'])
        ->middleware('permission:immunizations')
        ->name('immunizations.checkin');
    Route::get('/immunizations/enroll-infant', [ImmunizationController::class, 'createInfant'])
        ->middleware('permission:immunizations')
        ->name('immunizations.enroll-infant.create');
    Route::get('/patients/{id}/immunizations', [ImmunizationController::class, 'forPatient'])
        ->middleware('permission:immunizations')
        ->name('immunizations.patient');
    Route::get('/patients/{id}/immunizations/print', [ImmunizationController::class, 'printRecord'])
        ->middleware('permission:immunizations')
        ->name('immunizations.print-card');
    Route::get('/patients/{id}/immunizations/print/pdf', [ImmunizationController::class, 'downloadPrintCardPdf'])
        ->middleware('permission:immunizations')
        ->name('immunizations.print-card.pdf');
    Route::post('/patients/{id}/immunizations/administer', [ImmunizationController::class, 'administer'])
        ->middleware('permission:immunizations')
        ->name('immunizations.administer');
    Route::post('/patients/{id}/immunizations/{vaccine}/mark-done', [ImmunizationController::class, 'markGiven'])
        ->middleware('permission:immunizations')
        ->name('immunizations.mark-done');
    Route::post('/patients/{patient}/immunizations/enroll', [ImmunizationController::class, 'enroll'])
        ->middleware('permission:immunizations')
        ->name('immunizations.enroll');
    Route::post('/immunizations/infants', [ImmunizationController::class, 'enrollInfant'])
        ->middleware('permission:immunizations')
        ->name('immunizations.enroll-infant');
    Route::post('/immunizations/no-show', [ImmunizationController::class, 'toggleNoShow'])
        ->middleware('permission:immunizations')
        ->name('immunizations.no-show');
    Route::get('/immunizations/household-match', [ImmunizationController::class, 'householdMatch'])
        ->middleware('permission:immunizations', 'throttle:60,1')
        ->name('immunizations.household-match');

    // 5a. MATERNAL CARE (Family Planning / Prenatal / Postnatal)
    Route::get('/maternal/family-planning', [FamilyPlanningController::class, 'index'])
        ->middleware('permission:maternal')
        ->name('maternal.family-planning.index');
    Route::get('/patients/{patient}/family-planning', [FamilyPlanningController::class, 'patient'])
        ->middleware('permission:maternal')
        ->name('maternal.family-planning.patient');
    Route::post('/patients/{patient}/family-planning', [FamilyPlanningController::class, 'store'])
        ->middleware('permission:maternal')
        ->name('maternal.family-planning.store');
    Route::put('/maternal/family-planning/{client}', [FamilyPlanningController::class, 'update'])
        ->middleware('permission:maternal')
        ->name('maternal.family-planning.update');
    Route::post('/maternal/family-planning/{client}/visits', [FamilyPlanningController::class, 'addVisit'])
        ->middleware('permission:maternal')
        ->name('maternal.family-planning.visits.store');
    Route::get('/maternal/family-planning/{client}/print', [FamilyPlanningController::class, 'print'])
        ->middleware('permission:maternal')
        ->name('maternal.family-planning.print');

    Route::get('/maternal/prenatal', [PrenatalController::class, 'index'])
        ->middleware('permission:maternal')
        ->name('maternal.prenatal.index');
    Route::get('/patients/{patient}/prenatal', [PrenatalController::class, 'patient'])
        ->middleware('permission:maternal')
        ->name('maternal.prenatal.patient');
    Route::post('/patients/{patient}/pregnancies', [PrenatalController::class, 'store'])
        ->middleware('permission:maternal')
        ->name('maternal.pregnancies.store');
    Route::put('/patients/{patient}/maternal-profile', [PrenatalController::class, 'updateProfile'])
        ->middleware('permission:maternal')
        ->name('maternal.profile.update');
    Route::put('/pregnancies/{pregnancy}', [PrenatalController::class, 'updatePregnancy'])
        ->middleware('permission:maternal')
        ->name('maternal.pregnancies.update');
    Route::post('/pregnancies/{pregnancy}/visits', [PrenatalController::class, 'addVisit'])
        ->middleware('permission:maternal')
        ->name('maternal.prenatal.visits.store');
    Route::put('/prenatal-visits/{visit}', [PrenatalController::class, 'updateVisit'])
        ->middleware('permission:maternal')
        ->name('maternal.prenatal.visits.update');
    Route::get('/pregnancies/{pregnancy}/print', [PrenatalController::class, 'print'])
        ->middleware('permission:maternal')
        ->name('maternal.pregnancies.print');

    Route::get('/maternal/postnatal', [PostnatalController::class, 'index'])
        ->middleware('permission:maternal')
        ->name('maternal.postnatal.index');
    Route::get('/patients/{patient}/postnatal', [PostnatalController::class, 'patient'])
        ->middleware('permission:maternal')
        ->name('maternal.postnatal.patient');
    Route::post('/patients/{patient}/postnatal', [PostnatalController::class, 'store'])
        ->middleware('permission:maternal')
        ->name('maternal.postnatal.store');
    Route::put('/postnatal/{postnatal}', [PostnatalController::class, 'update'])
        ->middleware('permission:maternal')
        ->name('maternal.postnatal.update');
    Route::post('/postnatal/{postnatal}/complete-visit', [PostnatalController::class, 'completePostpartumVisit'])
        ->middleware('permission:maternal')
        ->name('maternal.postnatal.complete-visit');
    Route::get('/postnatal/{postnatal}/print', [PostnatalController::class, 'print'])
        ->middleware('permission:maternal')
        ->name('maternal.postnatal.print');

    // 6. REPORTS (FHSIS)
    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('permission:reports')
        ->name('reports.index');
    Route::get('/reports/morbidity', [ReportController::class, 'morbidity'])
        ->middleware('permission:reports')
        ->name('reports.morbidity');
    Route::get('/reports/morbidity/download', [ReportController::class, 'downloadMorbidityPdf'])
        ->middleware('permission:reports')
        ->name('reports.morbidity.download');
    Route::get('/reports/mch-epi-fp', [ReportController::class, 'mchEpiFp'])
        ->middleware('permission:reports')
        ->name('reports.mch-epi-fp');
    Route::get('/reports/mch-epi-fp/download', [ReportController::class, 'downloadMchEpiFpPdf'])
        ->middleware('permission:reports')
        ->name('reports.mch-epi-fp.download');

    // Legacy redirects: standalone maternal / immunization / family planning
    // reports were merged into reports.mch-epi-fp.
    Route::get('/reports/maternal-care', [ReportController::class, 'redirectLegacyMchEpiFp'])
        ->middleware('permission:reports');
    Route::get('/reports/maternal-care/download', [ReportController::class, 'redirectLegacyMchEpiFpDownload'])
        ->middleware('permission:reports');
    Route::get('/reports/immunization', [ReportController::class, 'redirectLegacyMchEpiFp'])
        ->middleware('permission:reports');
    Route::get('/reports/immunization/download', [ReportController::class, 'redirectLegacyMchEpiFpDownload'])
        ->middleware('permission:reports');
    Route::get('/reports/family-planning', [ReportController::class, 'redirectLegacyMchEpiFp'])
        ->middleware('permission:reports');
    Route::get('/reports/family-planning/download', [ReportController::class, 'redirectLegacyMchEpiFpDownload'])
        ->middleware('permission:reports');

    // 8. USER MANAGEMENT
    Route::get('/users', [UserManagementController::class, 'index'])
        ->middleware('permission:users')
        ->name('users.index');
    Route::get('/users/create', [UserManagementController::class, 'create'])
        ->middleware('permission:users')
        ->name('users.create');
    Route::post('/users', [UserManagementController::class, 'store'])
        ->middleware('permission:users')
        ->name('users.store');
    Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])
        ->middleware('permission:users')
        ->name('users.edit');
    Route::put('/users/{user}', [UserManagementController::class, 'update'])
        ->middleware('permission:users')
        ->name('users.update');
    Route::post('/users/{user}/disable', [UserManagementController::class, 'disable'])
        ->middleware('permission:users')
        ->name('users.disable');
    Route::post('/users/{user}/enable', [UserManagementController::class, 'enable'])
        ->middleware('permission:users')
        ->name('users.enable');
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])
        ->middleware('permission:users')
        ->name('users.destroy');

    // 9. ROLE MANAGEMENT
    Route::get('/roles', [RoleManagementController::class, 'index'])
        ->middleware('permission:users')
        ->name('roles.index');
    Route::get('/roles/{role}/edit', [RoleManagementController::class, 'edit'])
        ->middleware('permission:users')
        ->name('roles.edit');
    Route::put('/roles/{role}', [RoleManagementController::class, 'update'])
        ->middleware('permission:users')
        ->name('roles.update');

    // 10. ZONE MANAGEMENT
    Route::get('/zones', [ZoneController::class, 'index'])
        ->middleware('permission:zones')
        ->name('zones.index');
    Route::get('/zones/create', [ZoneController::class, 'create'])
        ->middleware('permission:zones')
        ->name('zones.create');
    Route::post('/zones', [ZoneController::class, 'store'])
        ->middleware('permission:zones')
        ->name('zones.store');
    Route::get('/zones/{id}', [ZoneController::class, 'show'])
        ->middleware('permission:zones')
        ->name('zones.show');
    Route::get('/zones/{id}/edit', [ZoneController::class, 'edit'])
        ->middleware('permission:zones')
        ->name('zones.edit');
    Route::put('/zones/{id}', [ZoneController::class, 'update'])
        ->middleware('permission:zones')
        ->name('zones.update');
    Route::delete('/zones/{id}', [ZoneController::class, 'destroy'])
        ->middleware('permission:zones')
        ->name('zones.destroy');

    // 11. MEDICINE MANAGEMENT
    Route::get('/medicines', [MedicineController::class, 'index'])
        ->middleware('permission:medicines')
        ->name('medicines.index');
    Route::get('/medicines/create', [MedicineController::class, 'create'])
        ->middleware('permission:medicines')
        ->name('medicines.create');
    Route::post('/medicines', [MedicineController::class, 'store'])
        ->middleware('permission:medicines')
        ->name('medicines.store');
    Route::post('/medicines/import', [MedicineController::class, 'import'])
        ->middleware('permission:medicines')
        ->name('medicines.import');
    Route::post('/medicines/bulk-delete', [MedicineController::class, 'bulkDestroy'])
        ->middleware('permission:medicines')
        ->name('medicines.bulk-delete');
    Route::get('/medicines/{id}', [MedicineController::class, 'show'])
        ->middleware('permission:medicines')
        ->name('medicines.show');
    Route::get('/medicines/{id}/edit', [MedicineController::class, 'edit'])
        ->middleware('permission:medicines')
        ->name('medicines.edit');
    Route::put('/medicines/{id}', [MedicineController::class, 'update'])
        ->middleware('permission:medicines')
        ->name('medicines.update');
    Route::delete('/medicines/{id}', [MedicineController::class, 'destroy'])
        ->middleware('permission:medicines')
        ->name('medicines.destroy');

    // 12. SETTINGS
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/account', [SettingsController::class, 'account'])->name('settings.account');
    Route::post('/settings/account', [SettingsController::class, 'updateAccount'])->name('settings.account.update');
    Route::get('/settings/backups', [SettingsController::class, 'backups'])
        ->middleware('permission:users')
        ->name('settings.backups');
    Route::post('/settings/backups/export', [SettingsController::class, 'exportBackup'])
        ->middleware('permission:users', 'throttle:1,60')  // max 1 export per hour
        ->name('settings.backups.export');
    Route::post('/settings/backups/import', [SettingsController::class, 'importBackup'])
        ->middleware('permission:users', 'throttle:1,60')  // max 1 import per hour
        ->name('settings.backups.import');

    // 13. PROFILE MANAGEMENT
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/settings', [ProfileController::class, 'settings'])
        ->middleware('permission:users')
        ->name('profile.settings');
    Route::put('/profile/settings', [ProfileController::class, 'updateSettings'])
        ->middleware('permission:users')
        ->name('profile.settings.update');
    Route::get('/session/heartbeat', [SessionController::class, 'heartbeat'])->name('session.heartbeat');

    // 14. NOTIFICATIONS
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/mark-read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::post('/notifications/destroy-all', [NotificationController::class, 'destroyAll'])->name('notifications.destroy-all');

    // 15. MISC

}); // <--- End of Auth Group

// --- SESSION STATUS (public route: must report expiry, never redirect to HTML) ---
// The read-only session boot keeps this poll from refreshing last_activity,
// so an open-but-idle tab cannot act as a keep-alive.
Route::get('/session/status', [SessionController::class, 'status'])
    ->withoutMiddleware([
        StartSession::class,
        ShareErrorsFromSession::class,
    ])
    ->middleware(ReadOnlySession::class)
    ->name('session.status');

// --- CONSULTATION LIVE REQUESTS (outside the auth group) ---
// Polled every ~12s by the frontend; the read-only session keeps it from
// acting as a keep-alive, so the idle timeout applies to BHWs/doctors too.
// Middleware order matters: the session is read before auth runs.
Route::get('/consultations/live-requests', [ConsultationController::class, 'liveRequests'])
    ->withoutMiddleware([
        VerifyCsrfToken::class,
        StartSession::class,
        ShareErrorsFromSession::class,
    ])
    ->middleware(ReadOnlySession::class)
    ->name('consultations.live-requests');

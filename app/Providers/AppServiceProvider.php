<?php

namespace App\Providers;

use App\Models\ApplicationSetting;
use App\Models\Consultation;
use App\Models\Immunization;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\User;
use App\Policies\ImmunizationPolicy;
use App\Policies\MedicinePolicy;
use App\Policies\PatientPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set session lifetime from database setting
        try {
            $sessionTimeout = (int) ApplicationSetting::get('session_timeout', 120);
            Config::set('session.lifetime', max(5, $sessionTimeout));
        } catch (\Exception $e) {
            // Table might not exist during migrations or tests
            Config::set('session.lifetime', 120);
        }

        // Register authorization policies
        $this->registerPolicies();

        // Route-model binding: consultations resolve with their worker joined in,
        // matching the shape views expect (worker_first_name / worker_last_name).
        Route::bind('consultation', function (string $value) {
            return Consultation::query()
                ->leftJoin('health_workers', 'consultations.worker_id', '=', 'health_workers.id')
                ->leftJoin('health_workers as attending_doctor', 'consultations.attending_doctor_id', '=', 'attending_doctor.id')
                ->where('consultations.id', $value)
                ->select(
                    'consultations.*',
                    'health_workers.first_name as worker_first_name',
                    'health_workers.last_name as worker_last_name',
                    'attending_doctor.first_name as attending_doctor_first_name',
                    'attending_doctor.last_name as attending_doctor_last_name'
                )
                ->firstOrFail();
        });
    }

    /**
     * Register the application's authorization policies.
     */
    protected function registerPolicies(): void
    {
        Gate::policy(Patient::class, PatientPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Medicine::class, MedicinePolicy::class);
        Gate::policy(Immunization::class, ImmunizationPolicy::class);
        // Note: Consultation and Household don't have models, so policies are used directly in controllers
    }
}

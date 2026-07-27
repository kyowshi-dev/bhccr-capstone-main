<?php

namespace App\Providers;

use App\Models\ApplicationSetting;
use App\Models\Consultation;
use App\Models\Immunization;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\User;
use App\Observers\ConsultationObserver;
use App\Observers\PatientObserver;
use App\Observers\UserObserver;
use App\Policies\ImmunizationPolicy;
use App\Policies\MedicinePolicy;
use App\Policies\PatientPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
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
            $sessionTimeout = ApplicationSetting::get('session_timeout', 120);
            Config::set('session.lifetime', (int) $sessionTimeout);
        } catch (\Exception $e) {
            // Table might not exist during migrations or tests
            Config::set('session.lifetime', 120);
        }

        // Register model observers for audit logging
        Patient::observe(PatientObserver::class);
        User::observe(UserObserver::class);
        Consultation::observe(ConsultationObserver::class);

        // Register authorization policies
        $this->registerPolicies();
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

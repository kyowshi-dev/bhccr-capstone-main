<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Route;

class BreadcrumbHelper
{
    public static function getBreadcrumbs()
    {
        $routeName = Route::currentRouteName();
        $breadcrumbs = [];

        switch ($routeName) {
            case 'dashboard':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => null],
                ];
                break;

            case 'households.index':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Households', 'url' => null],
                ];
                break;

            case 'households.create':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Households', 'url' => route('households.index')],
                    ['name' => 'Add Household', 'url' => null],
                ];
                break;

            case 'households.edit':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Households', 'url' => route('households.index')],
                    ['name' => 'Edit Household', 'url' => null],
                ];
                break;

            case 'patients.index':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Resident Records', 'url' => null],
                ];
                break;

            case 'patients.create':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Resident Records', 'url' => route('patients.index')],
                    ['name' => 'Add Patient', 'url' => null],
                ];
                break;

            case 'patients.show':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Resident Records', 'url' => route('patients.index')],
                    ['name' => 'Patient Details', 'url' => null],
                ];
                break;

            case 'consultations.index':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Consultations', 'url' => null],
                ];
                break;

            case 'consultations.show':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Consultations', 'url' => route('consultations.index')],
                    ['name' => 'Consultation Details', 'url' => null],
                ];
                break;

            case 'consultations.edit':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Consultations', 'url' => route('consultations.index')],
                    ['name' => 'Consultation Details', 'url' => route('consultations.show', request()->route('consultation'))],
                    ['name' => 'Edit', 'url' => null],
                ];
                break;

            case 'referrals.index':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Referrals', 'url' => null],
                ];
                break;

            case 'immunizations.index':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Vaccinations', 'url' => null],
                ];
                break;

            case 'immunizations.patient':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Resident Records', 'url' => route('patients.index')],
                    ['name' => 'Patient Details', 'url' => route('patients.show', request()->route('id'))],
                    ['name' => 'Vaccinations', 'url' => null],
                ];
                break;

            case 'medicines.index':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Medicine Directory', 'url' => null],
                ];
                break;

            case 'medicines.create':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Medicine Directory', 'url' => route('medicines.index')],
                    ['name' => 'Add Medicine', 'url' => null],
                ];
                break;

            case 'medicines.show':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Medicine Directory', 'url' => route('medicines.index')],
                    ['name' => 'Medicine Details', 'url' => null],
                ];
                break;

            case 'medicines.edit':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Medicine Directory', 'url' => route('medicines.index')],
                    ['name' => 'Medicine Details', 'url' => route('medicines.show', request()->route('id'))],
                    ['name' => 'Edit Medicine', 'url' => null],
                ];
                break;

            case 'reports.index':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Reports', 'url' => null],
                ];
                break;

            case 'reports.morbidity':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Reports', 'url' => route('reports.index')],
                    ['name' => 'Morbidity Report', 'url' => null],
                ];
                break;

            case 'users.index':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Users', 'url' => null],
                ];
                break;

            case 'users.create':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Users', 'url' => route('users.index')],
                    ['name' => 'Add User', 'url' => null],
                ];
                break;

            case 'users.edit':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Users', 'url' => route('users.index')],
                    ['name' => 'Edit User', 'url' => null],
                ];
                break;

            case 'roles.index':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Roles & Permissions', 'url' => null],
                ];
                break;

            case 'roles.edit':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Roles & Permissions', 'url' => route('roles.index')],
                    ['name' => 'Edit Role', 'url' => null],
                ];
                break;

            case 'zones.index':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Zone Coverage', 'url' => null],
                ];
                break;

            case 'zones.create':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Zone Coverage', 'url' => route('zones.index')],
                    ['name' => 'Add Zone', 'url' => null],
                ];
                break;

            case 'zones.show':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Zone Coverage', 'url' => route('zones.index')],
                    ['name' => 'Zone Details', 'url' => null],
                ];
                break;

            case 'zones.edit':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Zone Coverage', 'url' => route('zones.index')],
                    ['name' => 'Zone Details', 'url' => route('zones.show', request()->route('id'))],
                    ['name' => 'Edit Zone', 'url' => null],
                ];
                break;

            case 'profile.show':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'My Profile', 'url' => null],
                ];
                break;

            case 'profile.edit':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'My Profile', 'url' => route('profile.show')],
                    ['name' => 'Edit Profile', 'url' => null],
                ];
                break;

            case 'profile.settings':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'My Profile', 'url' => route('profile.show')],
                    ['name' => 'Settings', 'url' => null],
                ];
                break;

            case 'notifications.index':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Notifications', 'url' => null],
                ];
                break;

            case 'settings.index':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Settings', 'url' => null],
                ];
                break;

            case 'settings.account':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Settings', 'url' => route('settings.index')],
                    ['name' => 'Account Settings', 'url' => null],
                ];
                break;

            case 'settings.backups':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Settings', 'url' => route('settings.index')],
                    ['name' => 'Backups', 'url' => null],
                ];
                break;

            default:
                $breadcrumbs = [];
                break;
        }

        return $breadcrumbs;
    }
}

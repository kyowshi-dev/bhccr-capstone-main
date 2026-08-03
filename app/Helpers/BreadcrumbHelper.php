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

            case 'patients.index':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Patients', 'url' => null],
                ];
                break;

            case 'patients.create':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Patients', 'url' => route('patients.index')],
                    ['name' => 'Add Patient', 'url' => null],
                ];
                break;

            case 'patients.show':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Patients', 'url' => route('patients.index')],
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

            case 'immunizations.index':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Immunizations', 'url' => null],
                ];
                break;

            case 'immunizations.patient':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Patients', 'url' => route('patients.index')],
                    ['name' => 'Patient Details', 'url' => route('patients.show', request()->route('id'))],
                    ['name' => 'Immunizations', 'url' => null],
                ];
                break;

            case 'medicines.index':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Medicines', 'url' => null],
                ];
                break;

            case 'medicines.create':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Medicines', 'url' => route('medicines.index')],
                    ['name' => 'Add Medicine', 'url' => null],
                ];
                break;

            case 'medicines.show':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Medicines', 'url' => route('medicines.index')],
                    ['name' => 'Medicine Details', 'url' => null],
                ];
                break;

            case 'medicines.edit':
                $breadcrumbs = [
                    ['name' => 'Dashboard', 'url' => route('dashboard')],
                    ['name' => 'Medicines', 'url' => route('medicines.index')],
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

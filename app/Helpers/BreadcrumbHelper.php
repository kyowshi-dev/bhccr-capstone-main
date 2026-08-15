<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Route;
use Throwable;

class BreadcrumbHelper
{
    private const TRAIL_KEY = 'breadcrumbs.trail';

    private const TRAIL_LIMIT = 6;

    private const DASHBOARD = 'dashboard';

    /**
     * Route name -> page label for every HTML page in the app.
     * Anything not listed here (search, exports, prints, polls) is never tracked.
     */
    public const PAGE_LABELS = [
        'dashboard' => 'Dashboard',
        'households.index' => 'Households',
        'households.create' => 'Add Household',
        'households.edit' => 'Edit Household',
        'patients.index' => 'Patients',
        'patients.create' => 'Add Patient',
        'patients.show' => 'Patient Details',
        'consultations.index' => 'Consultations',
        'consultations.create' => 'New Consultation',
        'consultations.show' => 'Consultation Details',
        'consultations.edit' => 'Edit Consultation',
        'referrals.index' => 'Referrals',
        'immunizations.index' => 'Vaccinations',
        'immunizations.enroll-infant.create' => 'Enroll Infant',
        'immunizations.patient' => 'Vaccination Record',
        'maternal.prenatal.index' => 'Prenatal',
        'maternal.prenatal.patient' => 'Prenatal Details',
        'maternal.postnatal.index' => 'Postnatal',
        'maternal.postnatal.patient' => 'Postnatal Details',
        'maternal.family-planning.index' => 'Family Planning',
        'maternal.family-planning.patient' => 'Family Planning Details',
        'reports.index' => 'Reports',
        'reports.morbidity' => 'Morbidity Report',
        'reports.mch-epi-fp' => 'Maternal, EPI & Family Planning',
        'medicines.index' => 'Medicines',
        'medicines.create' => 'Add Medicine',
        'medicines.show' => 'Medicine Details',
        'medicines.edit' => 'Edit Medicine',
        'zones.index' => 'Zones',
        'zones.create' => 'Add Zone',
        'zones.show' => 'Zone Details',
        'zones.edit' => 'Edit Zone',
        'users.index' => 'User Management',
        'users.create' => 'Add User',
        'users.edit' => 'Edit User',
        'roles.index' => 'Roles',
        'roles.edit' => 'Edit Role',
        'settings.index' => 'Settings',
        'settings.account' => 'Account Settings',
        'settings.backups' => 'Backups',
        'profile.show' => 'My Profile',
        'profile.edit' => 'Edit Profile',
        'profile.settings' => 'Session Settings',
        'notifications.index' => 'Notifications',
    ];

    /**
     * Static hierarchy used when there is no session trail (deep links).
     * Only pages with a parent are listed; the chain is rooted at Dashboard.
     */
    private const PARENTS = [
        'households.create' => 'households.index',
        'households.edit' => 'households.index',
        'patients.create' => 'patients.index',
        'patients.show' => 'patients.index',
        'consultations.create' => 'patients.index',
        'consultations.show' => 'consultations.index',
        'consultations.edit' => 'consultations.show',
        'immunizations.enroll-infant.create' => 'immunizations.index',
        'immunizations.patient' => 'immunizations.index',
        'maternal.prenatal.patient' => 'maternal.prenatal.index',
        'maternal.postnatal.patient' => 'maternal.postnatal.index',
        'maternal.family-planning.patient' => 'maternal.family-planning.index',
        'reports.morbidity' => 'reports.index',
        'reports.mch-epi-fp' => 'reports.index',
        'medicines.create' => 'medicines.index',
        'medicines.show' => 'medicines.index',
        'medicines.edit' => 'medicines.show',
        'zones.create' => 'zones.index',
        'zones.show' => 'zones.index',
        'zones.edit' => 'zones.show',
        'users.create' => 'users.index',
        'users.edit' => 'users.index',
        'roles.edit' => 'roles.index',
        'settings.account' => 'settings.index',
        'settings.backups' => 'settings.index',
        'profile.edit' => 'profile.show',
        'profile.settings' => 'profile.show',
    ];

    public static function isPageRoute(?string $routeName): bool
    {
        return $routeName !== null && isset(self::PAGE_LABELS[$routeName]);
    }

    /**
     * Record the current page in the per-session navigation trail.
     * Called from TrackPageVisit middleware on successful HTML GET responses.
     */
    public static function recordCurrentVisit(): void
    {
        $routeName = Route::currentRouteName();

        if (! self::isPageRoute($routeName) || ! auth()->check()) {
            return;
        }

        $url = request()->url();
        $trail = session(self::TRAIL_KEY, []);

        if ($trail === []) {
            if ($routeName === self::DASHBOARD) {
                session([self::TRAIL_KEY => [self::crumb(self::DASHBOARD)]]);

                return;
            }

            // First page of the session (deep link): start from its static hierarchy.
            session([self::TRAIL_KEY => self::fallbackChain()]);

            return;
        }

        if ($routeName === self::DASHBOARD) {
            // Navigating back to the dashboard resets the trail.
            session([self::TRAIL_KEY => [self::crumb(self::DASHBOARD)]]);

            return;
        }

        $last = end($trail);
        if ($last !== false && $last['url'] === $url) {
            return;
        }

        // Revisiting an earlier page truncates the trail after it (back navigation).
        $existingIndex = array_search($url, array_column($trail, 'url'), true);
        if ($existingIndex !== false) {
            session([self::TRAIL_KEY => array_slice($trail, 0, $existingIndex + 1)]);

            return;
        }

        $trail[] = self::crumb($routeName, $url);
        if (count($trail) > self::TRAIL_LIMIT) {
            $trail = array_slice($trail, -self::TRAIL_LIMIT);
        }

        session([self::TRAIL_KEY => $trail]);
    }

    /**
     * @return array<int, array{name: string, url: string|null}>
     */
    public static function getBreadcrumbs(): array
    {
        $trail = session(self::TRAIL_KEY, []);

        if (count($trail) > 1) {
            return $trail;
        }

        // No trail yet (e.g. view rendered without a recorded visit):
        // fall back to the static hierarchy for the current route.
        return self::fallbackChain();
    }

    /**
     * @return array<int, array{name: string, url: string|null}>
     */
    private static function fallbackChain(): array
    {
        $routeName = Route::currentRouteName();

        if (! self::isPageRoute($routeName)) {
            return [];
        }

        $chain = [self::crumb($routeName, request()->url())];

        $current = $routeName;
        while (isset(self::PARENTS[$current])) {
            $parent = self::PARENTS[$current];
            $chain[] = self::crumb($parent, self::parentUrl($parent));
            $current = $parent;
        }

        $chain[] = self::crumb(self::DASHBOARD);

        return array_reverse($chain);
    }

    /**
     * @return array{name: string, url: string|null}
     */
    private static function crumb(string $routeName, ?string $url = null): array
    {
        return [
            'name' => self::PAGE_LABELS[$routeName],
            'url' => $url ?? route($routeName),
        ];
    }

    private static function parentUrl(string $routeName): ?string
    {
        $route = Route::getRoutes()->getByName($routeName);

        if ($route === null) {
            return null;
        }

        $params = [];
        foreach ($route->parameterNames() as $param) {
            $value = request()->route($param);
            if ($value === null) {
                return null;
            }
            $params[$param] = $value;
        }

        try {
            return route($routeName, $params);
        } catch (Throwable) {
            return null;
        }
    }
}

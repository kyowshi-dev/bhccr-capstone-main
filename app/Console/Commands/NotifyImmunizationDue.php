<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ChildImmunizationService;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class NotifyImmunizationDue extends Command
{
    protected $signature = 'notifications:immunization-due';

    protected $description = 'Send daily due and overdue immunization digests to staff';

    public function handle(ChildImmunizationService $service): int
    {
        $staff = User::query()
            ->where('is_active', true)
            ->with('role.permissions')
            ->get()
            ->filter(fn (User $user) => $user->hasPermission('immunizations'));

        foreach ($staff as $user) {
            $dueCount = $this->patientCount($service, $user, ChildImmunizationService::QUEUE_DUE);
            $overdueCount = $this->patientCount($service, $user, ChildImmunizationService::STATUS_OVERDUE);

            if ($dueCount > 0 && ! $this->alreadySentToday($user, 'immunization_due')) {
                $this->sendDigest($user, 'immunization_due', 'Children due for immunization', $dueCount);
            }

            if ($overdueCount > 0 && ! $this->alreadySentToday($user, 'immunization_overdue')) {
                $this->sendDigest($user, 'immunization_overdue', 'Children overdue for immunization', $overdueCount);
            }
        }

        return self::SUCCESS;
    }

    private function sendDigest(User $user, string $type, string $title, int $count): void
    {
        $noun = $count === 1 ? 'child' : 'children';
        $verb = $type === 'immunization_due' ? 'is due' : 'is overdue';
        $pluralVerb = $type === 'immunization_due' ? 'are due' : 'are overdue';

        NotificationService::send(
            $user,
            $type,
            $title,
            $count.' '.$noun.' '.($count === 1 ? $verb : $pluralVerb).' for immunization.',
            route('immunizations.index')
        );
    }

    private function patientCount(ChildImmunizationService $service, User $user, string $mode): int
    {
        if (! $user->isZoneScoped()) {
            return $service->queue($mode)->pluck('patient.id')->unique()->count();
        }

        $patientIds = [];

        foreach ($user->accessibleZoneIds() as $zoneId) {
            $patientIds = array_merge(
                $patientIds,
                $service->queue($mode, $zoneId)->pluck('patient.id')->unique()->all()
            );
        }

        return count(array_unique($patientIds));
    }

    private function alreadySentToday(User $user, string $type): bool
    {
        return $user->notifications()
            ->whereDate('created_at', Carbon::today())
            ->get()
            ->contains(fn ($notification) => ($notification->data['type'] ?? null) === $type);
    }
}

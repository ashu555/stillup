<?php

namespace App\Services;

use App\Enums\IncidentEventType;
use App\Enums\OrganizationRole;
use App\Models\Incident;
use App\Models\User;
use App\Notifications\IncidentOpenedNotification;
use App\Notifications\IncidentResolvedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class IncidentNotificationService
{
    /**
     * @return Collection<int, User>
     */
    public function recipients(Incident $incident): Collection
    {
        $incident->loadMissing('project.organization');

        return $incident->project->organization
            ->users()
            ->wherePivotIn('role', [
                OrganizationRole::Owner->value,
                OrganizationRole::Admin->value,
            ])
            ->get()
            ->unique('id')
            ->values();
    }

    public function notifyOpened(Incident $incident): void
    {
        $recipients = $this->recipients($incident);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new IncidentOpenedNotification($incident));

        $incident->forceFill([
            'last_notified_at' => now(),
        ])->save();

        $incident->events()->create([
            'type' => IncidentEventType::Notified,
            'message' => 'Opened notification emailed to owners/admins.',
            'user_id' => null,
            'meta' => [
                'notification' => 'opened',
                'recipient_ids' => $recipients->pluck('id')->all(),
            ],
            'created_at' => now(),
        ]);
    }

    public function notifyResolved(Incident $incident): void
    {
        $recipients = $this->recipients($incident);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new IncidentResolvedNotification($incident));

        $incident->forceFill([
            'last_notified_at' => now(),
        ])->save();

        $incident->events()->create([
            'type' => IncidentEventType::Notified,
            'message' => 'Resolved notification emailed to owners/admins.',
            'user_id' => null,
            'meta' => [
                'notification' => 'resolved',
                'recipient_ids' => $recipients->pluck('id')->all(),
            ],
            'created_at' => now(),
        ]);
    }
}

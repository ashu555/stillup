<?php

namespace App\Listeners;

use App\Events\IncidentResolved;
use App\Services\IncidentNotificationService;

class SendIncidentResolvedEmail
{
    public function __construct(
        private readonly IncidentNotificationService $notifications
    ) {}

    public function handle(IncidentResolved $event): void
    {
        $this->notifications->notifyResolved($event->incident);
    }
}

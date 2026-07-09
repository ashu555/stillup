<?php

namespace App\Listeners;

use App\Events\IncidentOpened;
use App\Services\IncidentNotificationService;

class SendIncidentOpenedEmail
{
    public function __construct(
        private readonly IncidentNotificationService $notifications
    ) {}

    public function handle(IncidentOpened $event): void
    {
        $this->notifications->notifyOpened($event->incident);
    }
}

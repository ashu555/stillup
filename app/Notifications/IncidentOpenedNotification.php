<?php

namespace App\Notifications;

use App\Models\Incident;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IncidentOpenedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Incident $incident
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $incident = $this->incident->loadMissing(['monitor.httpConfig', 'project.organization']);
        $organization = $incident->project->organization;
        $url = $incident->monitor->httpConfig?->url;
        $path = route('organizations.projects.incidents.show', [
            $organization->slug,
            $incident->project->slug,
            $incident->id,
        ]);

        return (new MailMessage)
            ->subject("[Stillup] Incident opened: {$incident->monitor->name}")
            ->greeting('Monitor is down')
            ->line($incident->summary)
            ->line("Project: {$incident->project->name}")
            ->line("Monitor: {$incident->monitor->name}")
            ->when($url, fn (MailMessage $mail) => $mail->line("URL: {$url}"))
            ->action('View incident', $path)
            ->line('Acknowledge the incident in Stillup once someone is investigating.');
    }
}

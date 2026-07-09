<?php

namespace App\Enums;

enum IncidentEventType: string
{
    case Opened = 'opened';
    case Acknowledged = 'acknowledged';
    case Resolved = 'resolved';
    case Notified = 'notified';
    case Comment = 'comment';

    public function label(): string
    {
        return match ($this) {
            self::Opened => 'Opened',
            self::Acknowledged => 'Acknowledged',
            self::Resolved => 'Resolved',
            self::Notified => 'Notified',
            self::Comment => 'Comment',
        };
    }
}

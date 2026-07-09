<?php

namespace App\Enums;

enum MonitorType: string
{
    case Http = 'http';
    case Heartbeat = 'heartbeat';

    public function label(): string
    {
        return match ($this) {
            self::Http => 'HTTP',
            self::Heartbeat => 'Heartbeat',
        };
    }
}

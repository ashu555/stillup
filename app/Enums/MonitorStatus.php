<?php

namespace App\Enums;

enum MonitorStatus: string
{
    case Up = 'up';
    case Down = 'down';
    case Degraded = 'degraded';
    case Paused = 'paused';
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Up => 'Up',
            self::Down => 'Down',
            self::Degraded => 'Degraded',
            self::Paused => 'Paused',
            self::Pending => 'Pending',
        };
    }
}

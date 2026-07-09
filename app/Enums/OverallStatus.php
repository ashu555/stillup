<?php

namespace App\Enums;

enum OverallStatus: string
{
    case Operational = 'operational';
    case Degraded = 'degraded';
    case MajorOutage = 'major_outage';
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Operational => 'Operational',
            self::Degraded => 'Degraded',
            self::MajorOutage => 'Major outage',
            self::Pending => 'Pending',
        };
    }
}

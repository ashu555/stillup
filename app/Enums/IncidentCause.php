<?php

namespace App\Enums;

enum IncidentCause: string
{
    case HttpFailure = 'http_failure';
    case HeartbeatMiss = 'heartbeat_miss';

    public function label(): string
    {
        return match ($this) {
            self::HttpFailure => 'HTTP failure',
            self::HeartbeatMiss => 'Heartbeat miss',
        };
    }
}

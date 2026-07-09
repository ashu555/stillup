<?php

namespace App\Enums;

enum IncidentCause: string
{
    case HttpFailure = 'http_failure';

    public function label(): string
    {
        return match ($this) {
            self::HttpFailure => 'HTTP failure',
        };
    }
}

<?php

namespace App\Events;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentAcknowledged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Incident $incident,
        public User $user
    ) {}
}

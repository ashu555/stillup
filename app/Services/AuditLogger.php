<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    public function log(
        string $action,
        ?User $user = null,
        ?Organization $organization = null,
        ?Model $auditable = null,
        array $properties = []
    ): AuditLog {
        return AuditLog::query()->create([
            'user_id' => $user?->id,
            'organization_id' => $organization?->id,
            'action' => $action,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'properties' => $properties ?: null,
            'ip_address' => Request::ip(),
        ]);
    }
}

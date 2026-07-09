<?php

namespace App\Models;

use App\Enums\IncidentCause;
use App\Enums\IncidentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incident extends Model
{
    /** @use HasFactory<\Database\Factories\IncidentFactory> */
    use HasFactory;

    protected $fillable = [
        'monitor_id',
        'project_id',
        'status',
        'cause',
        'summary',
        'opened_at',
        'acknowledged_at',
        'acknowledged_by',
        'resolved_at',
        'resolved_by',
        'last_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => IncidentStatus::class,
            'cause' => IncidentCause::class,
            'opened_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
            'last_notified_at' => 'datetime',
        ];
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(IncidentEvent::class);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            IncidentStatus::Open->value,
            IncidentStatus::Acknowledged->value,
        ]);
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }
}

<?php

namespace App\Models;

use App\Enums\IncidentEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'incident_id',
        'type',
        'message',
        'user_id',
        'meta',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => IncidentEventType::class,
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (IncidentEvent $event): void {
            if ($event->created_at === null) {
                $event->created_at = now();
            }
        });
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

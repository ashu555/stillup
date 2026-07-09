<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class HeartbeatMonitorConfig extends Model
{
    /** @use HasFactory<\Database\Factories\HeartbeatMonitorConfigFactory> */
    use HasFactory;

    protected $fillable = [
        'monitor_id',
        'token',
        'expected_every_seconds',
        'grace_seconds',
        'last_heartbeat_at',
    ];

    protected function casts(): array
    {
        return [
            'expected_every_seconds' => 'integer',
            'grace_seconds' => 'integer',
            'last_heartbeat_at' => 'datetime',
        ];
    }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    public function pingUrl(): string
    {
        return url('/heartbeat/'.$this->token);
    }

    public function isOverdue(): bool
    {
        if ($this->last_heartbeat_at === null) {
            return false;
        }

        $deadline = $this->last_heartbeat_at
            ->copy()
            ->addSeconds($this->expected_every_seconds + $this->grace_seconds);

        return now()->greaterThan($deadline);
    }
}

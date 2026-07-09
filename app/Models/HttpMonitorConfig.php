<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HttpMonitorConfig extends Model
{
    /** @use HasFactory<\Database\Factories\HttpMonitorConfigFactory> */
    use HasFactory;

    protected $fillable = [
        'monitor_id',
        'url',
        'method',
        'expected_status',
        'timeout_seconds',
        'keyword',
    ];

    protected function casts(): array
    {
        return [
            'expected_status' => 'integer',
            'timeout_seconds' => 'integer',
        ];
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckResult extends Model
{
    /** @use HasFactory<\Database\Factories\CheckResultFactory> */
    use HasFactory;

    protected $fillable = [
        'monitor_id',
        'success',
        'status_code',
        'response_time_ms',
        'error_message',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'status_code' => 'integer',
            'response_time_ms' => 'integer',
            'checked_at' => 'datetime',
        ];
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }
}

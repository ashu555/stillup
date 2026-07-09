<?php

namespace App\Models;

use App\Enums\MonitorStatus;
use App\Enums\MonitorType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Monitor extends Model
{
    /** @use HasFactory<\Database\Factories\MonitorFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'type',
        'name',
        'is_enabled',
        'interval_seconds',
        'status',
        'last_checked_at',
        'last_status_change_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => MonitorType::class,
            'status' => MonitorStatus::class,
            'is_enabled' => 'boolean',
            'interval_seconds' => 'integer',
            'last_checked_at' => 'datetime',
            'last_status_change_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function httpConfig(): HasOne
    {
        return $this->hasOne(HttpMonitorConfig::class);
    }

    public function checkResults(): HasMany
    {
        return $this->hasMany(CheckResult::class);
    }

    public function isDue(): bool
    {
        if (! $this->is_enabled || $this->status === MonitorStatus::Paused) {
            return false;
        }

        if ($this->last_checked_at === null) {
            return true;
        }

        return $this->last_checked_at->lte(
            now()->subSeconds($this->interval_seconds)
        );
    }

    public function scopeHttp($query)
    {
        return $query->where('type', MonitorType::Http);
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeRunnable($query)
    {
        return $query
            ->enabled()
            ->where('status', '!=', MonitorStatus::Paused->value);
    }
}

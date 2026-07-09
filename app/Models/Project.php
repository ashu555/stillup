<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'public_status_enabled',
    ];

    protected function casts(): array
    {
        return [
            'public_status_enabled' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Project $project): void {
            if (blank($project->slug)) {
                $project->slug = static::uniqueSlugForOrganization(
                    $project->organization_id,
                    $project->name
                );
            }
        });
    }

    public static function uniqueSlugForOrganization(int $organizationId, string $name): string
    {
        $base = Str::slug($name) ?: 'project';
        $slug = $base;
        $counter = 1;

        while (
            static::query()
                ->where('organization_id', $organizationId)
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function monitors(): HasMany
    {
        return $this->hasMany(Monitor::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function resolveChildRouteBinding($childType, $value, $field)
    {
        if ($childType === 'monitor') {
            return $this->monitors()
                ->where($field ?? 'id', $value)
                ->firstOrFail();
        }

        if ($childType === 'incident') {
            return $this->incidents()
                ->where($field ?? 'id', $value)
                ->firstOrFail();
        }

        return parent::resolveChildRouteBinding($childType, $value, $field);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

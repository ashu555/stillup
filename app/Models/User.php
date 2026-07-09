<?php

namespace App\Models;

use App\Enums\OrganizationRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function roleIn(Organization $organization): ?OrganizationRole
    {
        $membership = $this->organizations()
            ->where('organizations.id', $organization->id)
            ->first();

        if (! $membership) {
            return null;
        }

        return OrganizationRole::tryFrom($membership->pivot->role);
    }

    public function belongsToOrganization(Organization $organization): bool
    {
        return $this->organizations()
            ->where('organizations.id', $organization->id)
            ->exists();
    }

    public function hasOrganizationRole(Organization $organization, OrganizationRole ...$roles): bool
    {
        $role = $this->roleIn($organization);

        if (! $role) {
            return false;
        }

        return in_array($role, $roles, true);
    }
}

<?php

namespace App\Enums;

enum OrganizationRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Member => 'Member',
            self::Viewer => 'Viewer',
        };
    }

    public function canManageOrganization(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }

    public function canManageProjects(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::Member], true);
    }

    public function canView(): bool
    {
        return true;
    }
}

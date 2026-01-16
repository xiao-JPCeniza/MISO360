<?php

namespace App\Enums;

enum Role: string
{
    case USER = 'user';
    case ADMIN = 'admin';
    case SUPER_ADMIN = 'super_admin';

    /**
     * Get the display name for the role.
     */
    public function displayName(): string
    {
        return match ($this) {
            self::USER => 'User',
            self::ADMIN => 'Admin',
            self::SUPER_ADMIN => 'Super Admin',
        };
    }

    /**
     * Check if this role can manage other users' roles.
     */
    public function canManageRoles(): bool
    {
        return match ($this) {
            self::USER => false,
            self::ADMIN, self::SUPER_ADMIN => true,
        };
    }

    /**
     * Check if this role has admin privileges.
     */
    public function isAdmin(): bool
    {
        return match ($this) {
            self::USER => false,
            self::ADMIN, self::SUPER_ADMIN => true,
        };
    }

    /**
     * Check if this role has super admin privileges.
     */
    public function isSuperAdmin(): bool
    {
        return $this === self::SUPER_ADMIN;
    }

    /**
     * Get all role values as array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all roles as key-value pairs for forms.
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(function (self $role) {
            return [$role->value => $role->displayName()];
        })->toArray();
    }
}

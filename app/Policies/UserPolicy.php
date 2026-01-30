<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageRoles();
    }

    public function view(User $user, User $target): bool
    {
        return $user->canManageRoles();
    }

    public function update(User $user, User $target): bool
    {
        return $this->canManageTarget($user, $target);
    }

    public function updateRole(User $user, User $target): bool
    {
        return $this->canManageTarget($user, $target);
    }

    public function updateStatus(User $user, User $target): bool
    {
        return $this->canManageTarget($user, $target);
    }

    public function updatePassword(User $user, User $target): bool
    {
        return $this->canManageTarget($user, $target);
    }

    private function canManageTarget(User $user, User $target): bool
    {
        if (! $user->canManageRoles()) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return ! $target->isSuperAdmin();
    }
}

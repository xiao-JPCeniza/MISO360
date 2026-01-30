<?php

namespace App\Policies;

use App\Models\TicketEnrollment;
use App\Models\User;

class TicketEnrollmentPolicy
{
    public function view(User $user, TicketEnrollment $enrollment): bool
    {
        return true;
    }

    public function review(User $user, TicketEnrollment $enrollment): bool
    {
        return $user->isAdmin();
    }

    public function assign(User $user, TicketEnrollment $enrollment): bool
    {
        return $user->isSuperAdmin();
    }
}

<?php

namespace App\Providers;

use App\Models\TicketEnrollment;
use App\Models\User;
use App\Policies\TicketEnrollmentPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(TicketEnrollment::class, TicketEnrollmentPolicy::class);
    }
}

<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubmitOnlyRouteAccess
{
    private const SUBMIT_ONLY_EMAIL = 'request@miso.gov.ph';

    private const ALLOWED_ROUTE_NAMES = [
        'submit-request',
        'submit-request.store',
    ];

    private static bool $submitOnlyIdResolved = false;

    private static ?int $submitOnlyUserId = null;

    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user || ! $this->isRestrictedSubmitOnlyAccount($user)) {
            return $next($request);
        }

        if ($request->routeIs(...self::ALLOWED_ROUTE_NAMES)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'Submit-only users may only access the ticket submission route.');
        }

        return redirect()->route('submit-request');
    }

    private function isRestrictedSubmitOnlyAccount(User $user): bool
    {
        if ($user->role !== Role::SUBMIT_ONLY) {
            return false;
        }

        if (! self::$submitOnlyIdResolved) {
            self::$submitOnlyUserId = User::query()
                ->where('email', self::SUBMIT_ONLY_EMAIL)
                ->value('id');
            self::$submitOnlyIdResolved = true;
        }

        if (self::$submitOnlyUserId !== null) {
            return $user->id === self::$submitOnlyUserId;
        }

        return strcasecmp($user->email, self::SUBMIT_ONLY_EMAIL) === 0;
    }
}

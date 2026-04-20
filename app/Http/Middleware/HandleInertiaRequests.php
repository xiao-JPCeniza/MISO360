<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Notifications\Admin\AdminInAppNotificationKinds;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;
use Throwable;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'csrf_token' => $request->session()->token(),
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'status' => $request->session()->get('status'),
                'warning' => $request->session()->get('warning'),
                'info' => $request->session()->get('info'),
            ],
            'notifications' => $user ? [
                'unreadCount' => $this->resolveUnreadNotificationCount($user),
            ] : null,
        ];
    }

    private function resolveUnreadNotificationCount(mixed $user): int
    {
        if (! $this->notificationsTableExists()) {
            return 0;
        }

        try {
            $query = $user->unreadNotifications();
            if ($user instanceof User && $user->isAdmin()) {
                $query->whereIn('data->kind', AdminInAppNotificationKinds::all());
            }

            return (int) $query->count();
        } catch (Throwable $exception) {
            if (! $this->isMissingNotificationsTableException($exception)) {
                report($exception);
            }

            return 0;
        }
    }

    private function notificationsTableExists(): bool
    {
        try {
            return Schema::hasTable('notifications');
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    private function isMissingNotificationsTableException(Throwable $exception): bool
    {
        if (! $exception instanceof QueryException) {
            return false;
        }

        $errorInfo = $exception->errorInfo ?? [];
        $driverCode = (string) ($errorInfo[1] ?? '');
        $sqlState = (string) ($errorInfo[0] ?? '');

        if ($driverCode === '1146') {
            return true;
        }

        if ($sqlState === 'HY000' && str_contains(strtolower($exception->getMessage()), 'no such table')) {
            return true;
        }

        return false;
    }
}

<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NotificationTestController extends Controller
{
    /**
     * Local / testing only: page with links that trigger session flash toasts.
     */
    public function index(): Response
    {
        return Inertia::render('dev/NotificationTest');
    }

    public function flash(string $type): RedirectResponse
    {
        $target = redirect()->route('dev.notifications-test');

        return match ($type) {
            'success' => $target->with('success', 'Sample success toast (flash success).'),
            'error' => $target->with('error', 'Sample error toast (flash error).'),
            'warning' => $target->with('warning', 'Sample warning toast (flash warning).'),
            'info' => $target->with('info', 'Sample info toast (flash info).'),
            'status' => $target->with('status', 'Sample status toast (treated like success).'),
            default => throw new NotFoundHttpException,
        };
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        if (! $this->notificationsTableExists()) {
            return response()->json([
                'unreadCount' => 0,
                'items' => [],
            ]);
        }

        $unreadCount = $user->unreadNotifications()->count();

        $items = $user->notifications()
            ->latest()
            ->limit(8)
            ->get()
            ->map(function (DatabaseNotification $n): array {
                return [
                    'id' => $n->id,
                    'readAt' => $n->read_at?->toIso8601String(),
                    'createdAt' => $n->created_at?->toIso8601String(),
                    'data' => $n->data,
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'unreadCount' => $unreadCount,
            'items' => $items,
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        if (! $this->notificationsTableExists()) {
            return response()->json(['ok' => true]);
        }

        $user->unreadNotifications()->update([
            'read_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function visit(Request $request, DatabaseNotification $notification): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        if ($notification->notifiable_type !== $user::class || (string) $notification->notifiable_id !== (string) $user->id) {
            abort(404);
        }

        if (! $notification->read_at) {
            $notification->markAsRead();
        }

        $returnUrl = is_array($notification->data) ? ($notification->data['url'] ?? null) : null;
        $returnUrl = is_string($returnUrl) && trim($returnUrl) !== '' ? $returnUrl : '/dashboard';

        return Inertia::render('notifications/Visit', [
            'externalUrl' => 'https://feedback.manolofortich.gov.ph/',
            'returnUrl' => $returnUrl,
            'notificationId' => $notification->id,
        ]);
    }

    private function notificationsTableExists(): bool
    {
        try {
            return Schema::hasTable('notifications');
        } catch (Throwable $exception) {
            if (! $this->isMissingNotificationsTableException($exception)) {
                report($exception);
            }

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

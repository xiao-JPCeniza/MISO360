<?php

namespace App\Notifications\Admin;

final class AdminInAppNotificationKinds
{
    public const NEW_USER_REGISTERED = 'new_user_registered';

    public const NEW_TICKET_REQUEST_SUBMITTED = 'new_ticket_request_submitted';

    /**
     * `data.kind` values for database notifications shown to admin and super admin in the app.
     * End-user-only kinds (e.g. ticket completed) are omitted so staff do not see requester alerts.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::NEW_USER_REGISTERED,
            self::NEW_TICKET_REQUEST_SUBMITTED,
        ];
    }

    public static function contains(?string $kind): bool
    {
        if ($kind === null || $kind === '') {
            return false;
        }

        return in_array($kind, self::all(), true);
    }
}

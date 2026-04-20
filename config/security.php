<?php

return [
    'two_factor' => [
        'code_ttl_minutes' => 10,
        'reverify_minutes' => 15,
        /*
         * When true, users who have verified their email and been approved by an
         * administrator skip the email OTP step at login (non-admin roles only).
         */
        'skip_for_fully_verified_users' => (bool) env('TWO_FACTOR_SKIP_FOR_VERIFIED', true),
    ],
];

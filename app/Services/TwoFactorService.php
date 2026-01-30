<?php

namespace App\Services;

use App\Models\TwoFactorChallenge;
use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Support\Facades\Hash;

class TwoFactorService
{
    public function createChallenge(User $user, string $purpose, ?string $ipAddress = null, ?string $userAgent = null): TwoFactorChallenge
    {
        $this->expireExistingChallenges($user, $purpose);

        $code = (string) random_int(100000, 999999);

        $challenge = TwoFactorChallenge::create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($this->codeTtlMinutes()),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        $user->notify(new TwoFactorCodeNotification(
            code: $code,
            expiresAt: $challenge->expires_at,
            purpose: $purpose,
        ));

        return $challenge;
    }

    public function verifyChallenge(User $user, string $purpose, string $code): bool
    {
        $challenge = TwoFactorChallenge::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $challenge) {
            return false;
        }

        if ($challenge->expires_at->isPast()) {
            $challenge->update(['consumed_at' => now()]);

            return false;
        }

        if (! Hash::check($code, $challenge->code_hash)) {
            return false;
        }

        $challenge->update(['consumed_at' => now()]);

        return true;
    }

    public function codeTtlMinutes(): int
    {
        return (int) config('security.two_factor.code_ttl_minutes', 10);
    }

    private function expireExistingChallenges(User $user, string $purpose): void
    {
        TwoFactorChallenge::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);
    }
}

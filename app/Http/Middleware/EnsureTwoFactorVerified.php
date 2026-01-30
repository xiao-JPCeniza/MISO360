<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $purpose = 'sensitive'): Response
    {
        $user = $request->user();

        if (! $user || ! $user->requiresTwoFactor()) {
            return $next($request);
        }

        $verifiedAt = $request->session()->get('two_factor.verified_at');

        if ($verifiedAt) {
            $verifiedTime = now()->setTimestamp((int) $verifiedAt);

            if ($verifiedTime->diffInMinutes() < $this->reverifyMinutes()) {
                return $next($request);
            }
        }

        $this->storeChallengeContext($request, $purpose);

        return $this->redirectToChallenge();
    }

    private function redirectToChallenge(): RedirectResponse
    {
        return redirect()->route('two-factor.challenge');
    }

    private function reverifyMinutes(): int
    {
        return (int) config('security.two_factor.reverify_minutes', 15);
    }

    private function storeChallengeContext(Request $request, string $purpose): void
    {
        $request->session()->put('two_factor.purpose', $purpose);
        $request->session()->put('two_factor.intended_url', $request->fullUrl());
        $request->session()->put('two_factor.pending_user_id', $request->user()?->id);
    }
}

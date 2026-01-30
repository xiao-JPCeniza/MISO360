<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorController extends Controller
{
    public function create(Request $request, TwoFactorService $twoFactorService): Response
    {
        $user = $this->resolveUser($request);
        $purpose = $this->resolvePurpose($request);

        if (! $user || ! $user->requiresTwoFactor()) {
            abort(403);
        }

        $twoFactorService->createChallenge(
            user: $user,
            purpose: $purpose,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return Inertia::render('auth/TwoFactorChallenge', [
            'email' => $user->email,
            'purpose' => $purpose,
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(Request $request, TwoFactorService $twoFactorService, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $this->resolveUser($request);
        $purpose = $this->resolvePurpose($request);

        if (! $user || ! $user->requiresTwoFactor()) {
            abort(403);
        }

        if (! $twoFactorService->verifyChallenge($user, $purpose, $data['code'])) {
            return back()->withErrors([
                'code' => 'The verification code is invalid or expired.',
            ]);
        }

        $request->session()->put('two_factor.verified_at', now()->timestamp);
        $request->session()->forget('two_factor.purpose');

        if (! $user->two_factor_confirmed_at) {
            $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        }

        $auditLogger->log($request, 'two_factor.challenge.verified', $user, [
            'purpose' => $purpose,
        ]);

        if ($request->session()->has('two_factor.pending_user_id')) {
            $request->session()->forget('two_factor.pending_user_id');
            auth()->loginUsingId($user->id);
            $request->session()->regenerate();
        }

        return redirect()->to($this->resolveIntendedUrl($request));
    }

    public function resend(Request $request, TwoFactorService $twoFactorService): RedirectResponse
    {
        $user = $this->resolveUser($request);
        $purpose = $this->resolvePurpose($request);

        if (! $user || ! $user->requiresTwoFactor()) {
            abort(403);
        }

        $twoFactorService->createChallenge(
            user: $user,
            purpose: $purpose,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return back()->with('status', 'A new verification code has been sent.');
    }

    private function resolveUser(Request $request): ?User
    {
        $pendingUserId = $request->session()->get('two_factor.pending_user_id');

        if ($pendingUserId) {
            return User::find($pendingUserId);
        }

        return $request->user();
    }

    private function resolvePurpose(Request $request): string
    {
        return $request->session()->get('two_factor.purpose', $request->user() ? 'sensitive' : 'login');
    }

    private function resolveIntendedUrl(Request $request): string
    {
        $intended = $request->session()->pull('two_factor.intended_url');

        return $intended ?: route('dashboard');
    }
}

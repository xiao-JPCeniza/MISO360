<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ReferenceValueGroup;
use App\Http\Controllers\Controller;
use App\Models\ReferenceValue;
use App\Services\AuditLogger;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        $offices = ReferenceValue::query()
            ->forGroup(ReferenceValueGroup::OfficeDesignation)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('auth/Login', [
            'offices' => $offices,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request, TwoFactorService $twoFactorService, AuditLogger $auditLogger): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = $request->user();

        if ($user && ! $user->isActive()) {
            Auth::logout();

            return back()->withErrors([
                'email' => 'Your account is inactive. Please contact an administrator.',
            ])->onlyInput('email');
        }

        $target = $this->resolveRedirectTarget($user);

        if ($user && $user->requiresTwoFactor()) {
            $request->session()->put('two_factor.pending_user_id', $user->id);
            $request->session()->put('two_factor.purpose', 'login');
            $request->session()->put('two_factor.intended_url', $target);

            $twoFactorService->createChallenge(
                user: $user,
                purpose: 'login',
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );

            $auditLogger->log($request, 'two_factor.challenge.sent', $user, [
                'purpose' => 'login',
            ]);

            Auth::logout();

            return redirect()->route('two-factor.challenge');
        }

        return redirect()->intended($target);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function resolveRedirectTarget(?object $user): string
    {
        if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return route('admin.dashboard');
        }

        return route('dashboard');
    }
}

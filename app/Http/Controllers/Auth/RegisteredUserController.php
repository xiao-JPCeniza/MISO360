<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ReferenceValueGroup;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRegisteredUserRequest;
use App\Models\ReferenceValue;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        $offices = ReferenceValue::query()
            ->forGroup(ReferenceValueGroup::OfficeDesignation)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('auth/Login', [
            'canRegister' => true,
            'offices' => $offices,
            'initialPanel' => 'register',
        ]);
    }

    public function store(StoreRegisteredUserRequest $request, TwoFactorService $twoFactorService): RedirectResponse
    {
        $data = $request->validated();

        $user = null;

        try {
            $user = User::create([
                'name' => $data['name'],
                'position_title' => $data['position_title'],
                'office_designation_id' => $data['office_designation_id'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => Role::USER,
                'is_active' => true,
                'two_factor_enabled' => true,
                'workos_id' => Str::uuid()->toString(),
                'avatar' => '',
            ]);

            $request->session()->put('two_factor.pending_user_id', $user->id);
            $request->session()->put('two_factor.purpose', 'login');
            $request->session()->put('two_factor.intended_url', route('dashboard'));

            $twoFactorService->createChallenge(
                user: $user,
                purpose: 'login',
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );

            return redirect()->route('two-factor.challenge');
        } catch (Throwable $e) {
            if ($user !== null) {
                $user->delete();
            }

            Log::error('Registration failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'email' => 'Something went wrong on our end. Please try again later.',
            ])->onlyInput('name', 'email', 'position_title', 'office_designation_id');
        }
    }
}

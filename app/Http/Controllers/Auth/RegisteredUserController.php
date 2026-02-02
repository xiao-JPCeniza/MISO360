<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRegisteredUserRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RegisteredUserController extends Controller
{
    public function store(StoreRegisteredUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'position_title' => $data['position_title'],
            'office_designation_id' => $data['office_designation_id'],
            'email' => $data['email'],
            'password' => $data['password'],
            'email_verified_at' => now(),
            'role' => Role::USER,
            'is_active' => true,
            'two_factor_enabled' => true,
            'workos_id' => Str::uuid()->toString(),
            'avatar' => '',
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}

<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileAvatarUpdateRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\User;
use App\Services\AvatarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile settings.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->update(['name' => $request->name]);

        return to_route('profile.edit');
    }

    /**
     * Update the authenticated user's profile picture.
     * Stores in storage/app/public/avatars/{id}/, updates user.avatar (path only).
     * Shared auth.user includes avatar_url accessor so the UI updates after redirect/reload.
     * Ensure "php artisan storage:link" has been run so /storage/avatars/... is publicly accessible.
     */
    public function updateAvatar(ProfileAvatarUpdateRequest $request, AvatarService $avatarService): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return back()->withErrors(['avatar' => 'Unable to update avatar.']);
        }

        $file = $request->file('avatar');
        if (! $file || ! $file->isValid()) {
            return back()->withErrors(['avatar' => 'The uploaded file is invalid. Please try again.']);
        }

        try {
            $path = $avatarService->storeAvatar($file, $user);
            $user->update(['avatar' => $path]);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['avatar' => 'Failed to save photo. Please try again.']);
        }

        return redirect()->route('profile.edit')->with('status', 'Profile photo updated.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($user instanceof User) {
            $user->delete();
        }

        return redirect('/');
    }
}

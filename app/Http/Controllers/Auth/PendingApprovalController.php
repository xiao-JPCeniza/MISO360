<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PendingApprovalController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user && (! $user->requiresManualAdminVerification() || $user->isApprovedByAdmin())) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('auth/PendingApproval');
    }
}

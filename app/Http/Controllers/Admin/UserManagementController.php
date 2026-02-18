<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReferenceValueGroup;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserPasswordRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Http\Requests\Admin\UpdateUserWorkRequest;
use App\Models\AuditLog;
use App\Models\ReferenceValue;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $users = User::query()
            ->select([
                'id',
                'name',
                'email',
                'phone',
                'role',
                'is_active',
                'two_factor_enabled',
                'created_at',
            ])
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'roleOptions' => Role::options(),
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        Gate::authorize('view', $user);

        $user->load('officeDesignation');

        $officeDesignations = ReferenceValue::query()
            ->active()
            ->forGroup(ReferenceValueGroup::OfficeDesignation)
            ->orderBy('name')
            ->get(['id', 'name']);

        $auditLogs = AuditLog::query()
            ->where('target_type', $user->getMorphClass())
            ->where('target_id', $user->id)
            ->latest()
            ->limit(25)
            ->get();

        return Inertia::render('admin/users/Show', [
            'user' => $user->only([
                'id',
                'name',
                'email',
                'phone',
                'role',
                'is_active',
                'two_factor_enabled',
                'two_factor_confirmed_at',
                'office_designation_id',
                'position_title',
                'created_at',
                'updated_at',
            ]),
            'officeDesignation' => $user->officeDesignation?->only(['id', 'name']),
            'officeDesignations' => $officeDesignations,
            'roleOptions' => Role::options(),
            'auditLogs' => $auditLogs,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        Gate::authorize('update', $user);

        $validated = $request->validated();
        $previousValues = $user->only(array_keys($validated));

        $user->update($validated);

        $changes = [];
        foreach ($validated as $field => $value) {
            $previous = $previousValues[$field] ?? null;
            if ($previous !== $value) {
                $changes[$field] = ['from' => $previous, 'to' => $value];
            }
        }

        $auditLogger->log($request, 'user.profile.updated', $user, [
            'fields' => array_keys($request->validated()),
            'changes' => $changes,
        ]);

        return back()->with('status', 'User profile updated.');
    }

    public function updateWork(UpdateUserWorkRequest $request, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        Gate::authorize('update', $user);

        $validated = $request->validated();
        $previousValues = $user->only(array_keys($validated));

        $officeNames = ReferenceValue::query()
            ->whereIn('id', [
                $user->office_designation_id,
                $validated['office_designation_id'],
            ])
            ->pluck('name', 'id');

        $user->update($validated);

        $changes = [];
        foreach ($validated as $field => $value) {
            $previous = $previousValues[$field] ?? null;
            if ($previous === $value) {
                continue;
            }
            if ($field === 'office_designation_id') {
                $changes['office_designation'] = [
                    'from' => [
                        'id' => $previous,
                        'name' => $officeNames[$previous] ?? null,
                    ],
                    'to' => [
                        'id' => $value,
                        'name' => $officeNames[$value] ?? null,
                    ],
                ];

                continue;
            }
            $changes[$field] = ['from' => $previous, 'to' => $value];
        }

        $auditLogger->log($request, 'user.work.updated', $user, [
            'fields' => array_keys($request->validated()),
            'changes' => $changes,
        ]);

        return back()->with('status', 'Work details updated.');
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        Gate::authorize('updateRole', $user);

        $user->update([
            'role' => $request->validated()['role'],
        ]);

        $auditLogger->log($request, 'user.role.updated', $user, [
            'role' => $user->role->value,
        ]);

        return back()->with('status', 'User role updated.');
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        Gate::authorize('updateStatus', $user);

        $user->update([
            'is_active' => (bool) $request->validated()['is_active'],
        ]);

        $auditLogger->log($request, 'user.status.updated', $user, [
            'is_active' => $user->is_active,
        ]);

        return back()->with('status', 'User status updated.');
    }

    public function updatePassword(UpdateUserPasswordRequest $request, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        Gate::authorize('updatePassword', $user);

        $user->update([
            'password' => $request->validated()['password'],
        ]);

        $auditLogger->log($request, 'user.password.updated', $user);

        return back()->with('status', 'Password updated.');
    }
}

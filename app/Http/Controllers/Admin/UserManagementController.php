<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReferenceValueGroup;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListUsersRequest;
use App\Http\Requests\Admin\UpdateUserPasswordRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Http\Requests\Admin\UpdateUserWorkRequest;
use App\Models\AuditLog;
use App\Models\ReferenceValue;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class UserManagementController extends Controller
{
    public function index(ListUsersRequest $request): Response
    {
        $validated = $request->validated();
        $verification = $validated['verification'];
        $search = trim((string) ($validated['search'] ?? ''));

        $selectColumns = [
            'id',
            'name',
            'email',
            'phone',
            'role',
            'is_active',
            'two_factor_enabled',
            'created_at',
            'office_designation_id',
        ];

        if ($this->usersTableHasAdminVerifiedAtColumn()) {
            $selectColumns[] = 'admin_verified_at';
        }

        $query = User::query()
            ->select($selectColumns)
            ->with([
                'officeDesignation' => static function ($relation): void {
                    $relation->select('id', 'name');
                },
            ])
            ->orderBy('name');

        $this->applyVerificationFilter($query, $verification, $this->usersTableHasAdminVerifiedAtColumn());

        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(static function (Builder $builder) use ($like): void {
                $builder->where('users.name', 'like', $like)
                    ->orWhereHas('officeDesignation', static function (Builder $officeQuery) use ($like): void {
                        $officeQuery->where('name', 'like', $like);
                    });
            });
        }

        $users = $query->paginate(15)->withQueryString();

        if (! $this->usersTableHasAdminVerifiedAtColumn()) {
            $users->getCollection()->transform(function (User $user) {
                $user->setAttribute('admin_verified_at', null);

                return $user;
            });
        }

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'roleOptions' => Role::options(),
            'filters' => [
                'verification' => $verification,
                'search' => $search,
            ],
        ]);
    }

    private function applyVerificationFilter(Builder $query, string $verification, bool $hasAdminVerifiedAtColumn): void
    {
        if ($verification === 'all') {
            return;
        }

        $elevatedRoles = [Role::ADMIN->value, Role::SUPER_ADMIN->value];

        if ($verification === 'verified') {
            $query->where(static function (Builder $builder) use ($elevatedRoles, $hasAdminVerifiedAtColumn): void {
                $builder->whereIn('role', $elevatedRoles);
                if ($hasAdminVerifiedAtColumn) {
                    $builder->orWhereNotNull('admin_verified_at');
                }
            });

            return;
        }

        $query->whereNotIn('role', $elevatedRoles);

        if ($hasAdminVerifiedAtColumn) {
            $query->whereNull('admin_verified_at');
        }
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
            'user' => $this->showPayloadForUser($user),
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

    public function forceVerifyEmail(Request $request, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        Gate::authorize('update', $user);

        $user->update(['email_verified_at' => now()]);

        $auditLogger->log($request, 'user.email.force_verified', $user);

        return back()->with('status', 'Email marked as verified.');
    }

    public function toggleAdminVerification(Request $request, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        Gate::authorize('update', $user);

        if (! $this->usersTableHasAdminVerifiedAtColumn()) {
            return back()->with('error', 'Manual admin verification is unavailable until database migrations are up to date.');
        }

        if (! $user->requiresManualAdminVerification()) {
            abort(403, 'This account does not use manual admin verification.');
        }

        if ($user->isApprovedByAdmin()) {
            $user->update(['admin_verified_at' => null]);

            $auditLogger->log($request, 'user.admin_verification.revoked', $user);

            return back()->with('status', 'Manual verification removed. The user can no longer access the app until approved again.');
        }

        $user->update(['admin_verified_at' => now()]);

        $auditLogger->log($request, 'user.admin_verification.granted', $user);

        return back()->with('status', 'User verified. They may now access the application.');
    }

    public function deactivate(Request $request, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        Gate::authorize('deactivate', $user);

        $user->update(['is_active' => false]);
        $user->invalidateAllSessions();

        $auditLogger->log($request, 'user.deactivated', $user, [
            'is_active' => false,
        ]);

        return back()->with('status', 'Account deactivated. All sessions have been invalidated.');
    }

    public function destroy(Request $request, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $userId = $user->id;
        $user->invalidateAllSessions();

        $auditLogger->log($request, 'user.deleted', $user, [
            'user_id' => $userId,
        ]);

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('status', 'User account has been permanently deleted.');
    }

    private function showPayloadForUser(User $user): array
    {
        $payload = $user->only([
            'id',
            'name',
            'email',
            'email_verified_at',
            'phone',
            'role',
            'is_active',
            'two_factor_enabled',
            'two_factor_confirmed_at',
            'office_designation_id',
            'position_title',
            'created_at',
            'updated_at',
        ]);

        $payload['admin_verified_at'] = $this->usersTableHasAdminVerifiedAtColumn()
            ? $user->admin_verified_at
            : null;

        return $payload;
    }

    private function usersTableHasAdminVerifiedAtColumn(): bool
    {
        try {
            return Schema::hasColumn('users', 'admin_verified_at');
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }
}

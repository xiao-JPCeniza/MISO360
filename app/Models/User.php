<?php

namespace App\Models;

use App\Enums\Role;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that should be appended when the model is serialized.
     *
     * @var list<string>
     */
    protected $appends = ['avatar_url'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'position_title',
        'office_designation_id',
        'email',
        'password',
        'email_verified_at',
        'admin_verified_at',
        'role',
        'phone',
        'is_active',
        'two_factor_enabled',
        'two_factor_confirmed_at',
        'workos_id',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'workos_id',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'admin_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'role' => Role::class,
        ];
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(Role $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if the user has admin privileges.
     */
    public function isAdmin(): bool
    {
        return $this->role->isAdmin();
    }

    /**
     * Check if the user has super admin privileges.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role->isSuperAdmin();
    }

    /**
     * Check if the user has submit-only privileges.
     */
    public function isSubmitOnly(): bool
    {
        return $this->role === Role::SUBMIT_ONLY;
    }

    /**
     * Check if the user can submit requests with admin-like fields.
     */
    public function canSubmitAsPrivilegedRequester(): bool
    {
        return $this->isAdmin() || $this->isSubmitOnly();
    }

    /**
     * Check if the user can manage roles.
     */
    public function canManageRoles(): bool
    {
        return $this->role->canManageRoles();
    }

    /**
     * Check if the user is active.
     */
    public function isActive(): bool
    {
        return $this->is_active ?? true;
    }

    /**
     * Whether this account must be approved by an administrator before using the app.
     */
    public function requiresManualAdminVerification(): bool
    {
        return ! $this->role->isAdmin();
    }

    /**
     * Whether an administrator has approved this account for full access.
     */
    public function isApprovedByAdmin(): bool
    {
        return $this->admin_verified_at !== null;
    }

    /**
     * Check if two-factor authentication is required for this user.
     * Admin and super_admin skip 2FA (no email code) and go directly to the dashboard.
     */
    public function requiresTwoFactor(): bool
    {
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            return false;
        }

        return $this->two_factor_enabled !== false;
    }

    public function officeDesignation(): BelongsTo
    {
        return $this->belongsTo(ReferenceValue::class, 'office_designation_id');
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    /**
     * Get the full URL for the user's avatar (for display).
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (empty($this->avatar)) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar);
    }

    /**
     * Invalidate all sessions for this user (all devices). Used when deactivating or deleting.
     * Requires session driver to be "database" for full invalidation; other drivers are not supported.
     */
    public function invalidateAllSessions(): void
    {
        if (config('session.driver') !== 'database') {
            \Illuminate\Support\Facades\Log::warning('User session invalidation skipped: database session driver required', [
                'user_id' => $this->id,
                'session_driver' => config('session.driver'),
            ]);

            return;
        }

        DB::connection(config('session.connection'))
            ->table(config('session.table'))
            ->where('user_id', $this->id)
            ->delete();
    }
}

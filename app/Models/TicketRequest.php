<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketRequest extends Model
{
    /** @use HasFactory<\Database\Factories\TicketRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'control_ticket_number',
        'nature_of_request_id',
        'description',
        'has_qr_code',
        'qr_code_number',
        'attachments',
        'user_id',
        'requested_for_user_id',
        'office_designation_id',
        'status_id',
        'archived',
        'category_id',
        'remarks_id',
        'assigned_staff_id',
        'date_received',
        'date_started',
        'time_started',
        'estimated_completion_date',
        'time_completed',
        'action_taken',
        'equipment_network_details',
    ];

    protected $casts = [
        'attachments' => 'array',
        'equipment_network_details' => 'array',
        'has_qr_code' => 'boolean',
        'archived' => 'boolean',
        'date_received' => 'date',
        'date_started' => 'date',
        'time_started' => 'datetime',
        'estimated_completion_date' => 'date',
        'time_completed' => 'datetime',
    ];

    public function natureOfRequest(): BelongsTo
    {
        return $this->belongsTo(NatureOfRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function requestedForUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_for_user_id');
    }

    public function officeDesignation(): BelongsTo
    {
        return $this->belongsTo(ReferenceValue::class, 'office_designation_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ReferenceValue::class, 'status_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ReferenceValue::class, 'category_id');
    }

    public function remarks(): BelongsTo
    {
        return $this->belongsTo(ReferenceValue::class, 'remarks_id');
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }

    /**
     * QR/asset enrollment record when this request has a linked QR code.
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(TicketEnrollment::class, 'qr_code_number', 'unique_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereHas('status', function (Builder $q) {
            $q->where('name', '!=', 'Completed');
        })->orWhereNull('status_id');
    }

    /**
     * Requests that appear in the queue: Pending status only.
     * Excludes requests with no status selected (null status_id).
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereHas('status', fn (Builder $q) => $q->where('name', 'Pending'));
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereHas('status', function (Builder $q) {
            $q->where('name', 'Completed');
        });
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('archived', true);
    }

    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->where('archived', false);
    }

    /**
     * Borrow requests that are still active (not completed/returned).
     * Used for inventory "Borrowed" list so completed/returned items disappear.
     */
    public function scopeActivelyBorrowed(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereHas('status', function (Builder $statusQuery) {
                $statusQuery->whereNotIn('name', ['Completed', 'Returned']);
            })->orWhereNull('status_id');
        });
    }
}

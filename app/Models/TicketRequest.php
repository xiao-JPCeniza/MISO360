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
        'category_id',
    ];

    protected $casts = [
        'attachments' => 'array',
        'has_qr_code' => 'boolean',
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

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereHas('status', function (Builder $q) {
            $q->where('name', 'Completed');
        });
    }
}

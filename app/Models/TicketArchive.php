<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketArchive extends Model
{
    protected $fillable = [
        'unique_id',
        'equipment_name',
        'equipment_type',
        'brand',
        'model',
        'serial_number',
        'asset_tag',
        'supplier',
        'purchase_date',
        'expiry_date',
        'warranty_status',
        'equipment_image',
        'equipment_images',
        'spec_memory',
        'spec_storage',
        'spec_operating_system',
        'spec_network_address',
        'spec_accessories',
        'location_assigned_to',
        'location_office_division',
        'location_date_issued',
        'request_nature',
        'request_date',
        'request_action_taken',
        'request_assigned_staff',
        'request_remarks',
        'maintenance_date',
        'maintenance_remarks',
        'archived_at',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'expiry_date' => 'date',
        'location_date_issued' => 'date',
        'request_date' => 'date',
        'maintenance_date' => 'date',
        'archived_at' => 'datetime',
        'equipment_images' => 'array',
    ];
}

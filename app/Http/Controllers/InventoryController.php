<?php

namespace App\Http\Controllers;

use App\Models\TicketArchive;
use App\Models\TicketEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->trim()->toString();
        $status = strtolower($request->string('status')->toString() ?: 'all');

        $activeItems = collect();
        $archivedItems = collect();

        if ($status !== 'archived') {
            $activeItems = TicketEnrollment::query()
                ->when($search !== '', function ($query) use ($search) {
                    $query->where('equipment_name', 'like', "%{$search}%");
                })
                ->orderBy('equipment_name')
                ->get();
        }

        if ($status !== 'active') {
            $archivedItems = TicketArchive::query()
                ->when($search !== '', function ($query) use ($search) {
                    $query->where('equipment_name', 'like', "%{$search}%");
                })
                ->orderBy('equipment_name')
                ->get();
        }

        $items = $activeItems->map(fn ($item) => $this->mapItem($item, 'active'))
            ->merge($archivedItems->map(fn ($item) => $this->mapItem($item, 'archived')))
            ->values();

        return Inertia::render('inventory/Inventory', [
            'items' => $items,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'counts' => [
                'active' => TicketEnrollment::count(),
                'archived' => TicketArchive::count(),
            ],
        ]);
    }

    public function show(string $uniqueId)
    {
        $uniqueId = strtoupper(trim($uniqueId));

        $enrollment = TicketEnrollment::where('unique_id', $uniqueId)->first();
        if ($enrollment) {
            return Inertia::render('inventory/InventoryItem', [
                'item' => $this->mapDetail($enrollment, 'active'),
                'status' => 'active',
            ]);
        }

        $archived = TicketArchive::where('unique_id', $uniqueId)
            ->orderByDesc('archived_at')
            ->firstOrFail();

        return Inertia::render('inventory/InventoryItem', [
            'item' => $this->mapDetail($archived, 'archived'),
            'status' => 'archived',
        ]);
    }

    public function lookup(string $uniqueId)
    {
        $uniqueId = strtoupper(trim($uniqueId));

        $enrollment = TicketEnrollment::where('unique_id', $uniqueId)->first();
        if ($enrollment) {
            return response()->json([
                'exists' => true,
                'status' => 'active',
                'redirect' => route('inventory.show', ['uniqueId' => $uniqueId]),
            ]);
        }

        $archived = TicketArchive::where('unique_id', $uniqueId)->first();
        if ($archived) {
            return response()->json([
                'exists' => true,
                'status' => 'archived',
                'redirect' => route('inventory.show', ['uniqueId' => $uniqueId]),
            ]);
        }

        return response()->json([
            'exists' => false,
        ]);
    }

    public function archive(string $uniqueId)
    {
        $uniqueId = strtoupper(trim($uniqueId));

        return DB::transaction(function () use ($uniqueId) {
            $enrollment = TicketEnrollment::where('unique_id', $uniqueId)->firstOrFail();

            // Check if already archived to prevent duplicates
            $existingArchive = TicketArchive::where('unique_id', $uniqueId)->exists();
            if ($existingArchive) {
                return back()->withErrors([
                    'archive' => 'This item has already been archived.',
                ]);
            }

            TicketArchive::create([
                'unique_id' => $enrollment->unique_id,
                'equipment_name' => $enrollment->equipment_name,
                'equipment_type' => $enrollment->equipment_type,
                'brand' => $enrollment->brand,
                'model' => $enrollment->model,
                'serial_number' => $enrollment->serial_number,
                'asset_tag' => $enrollment->asset_tag,
                'supplier' => $enrollment->supplier,
                'purchase_date' => $enrollment->purchase_date,
                'expiry_date' => $enrollment->expiry_date,
                'warranty_status' => $enrollment->warranty_status,
                'equipment_image' => $enrollment->equipment_image,
                'equipment_images' => $enrollment->equipment_images,
                'spec_memory' => $enrollment->spec_memory,
                'spec_storage' => $enrollment->spec_storage,
                'spec_operating_system' => $enrollment->spec_operating_system,
                'spec_network_address' => $enrollment->spec_network_address,
                'spec_accessories' => $enrollment->spec_accessories,
                'location_assigned_to' => $enrollment->location_assigned_to,
                'location_office_division' => $enrollment->location_office_division,
                'location_date_issued' => $enrollment->location_date_issued,
                'request_nature' => $enrollment->request_nature,
                'request_date' => $enrollment->request_date,
                'request_action_taken' => $enrollment->request_action_taken,
                'request_assigned_staff' => $enrollment->request_assigned_staff,
                'request_remarks' => $enrollment->request_remarks,
                'maintenance_date' => $enrollment->maintenance_date,
                'maintenance_remarks' => $enrollment->maintenance_remarks,
                'archived_at' => now(),
            ]);

            $enrollment->delete();

            return redirect()->route('inventory.show', ['uniqueId' => $uniqueId]);
        });
    }

    private function mapItem(object $item, string $status): array
    {
        return [
            'id' => $item->id,
            'rowKey' => "{$status}-{$item->id}",
            'uniqueId' => $item->unique_id,
            'equipmentName' => $item->equipment_name,
            'equipmentType' => $item->equipment_type,
            'brand' => $item->brand,
            'model' => $item->model,
            'serialNumber' => $item->serial_number,
            'assetTag' => $item->asset_tag,
            'status' => $status,
            'archivedAt' => $status === 'archived' ? $item->archived_at?->toDateTimeString() : null,
        ];
    }

    private function mapDetail(object $item, string $status): array
    {
        return [
            'uniqueId' => $item->unique_id,
            'equipmentName' => $item->equipment_name,
            'equipmentType' => $item->equipment_type,
            'brand' => $item->brand,
            'model' => $item->model,
            'serialNumber' => $item->serial_number,
            'assetTag' => $item->asset_tag,
            'supplier' => $item->supplier,
            'purchaseDate' => optional($item->purchase_date)->format('Y-m-d'),
            'expiryDate' => optional($item->expiry_date)->format('Y-m-d'),
            'warrantyStatus' => $item->warranty_status,
            'equipmentImageUrls' => $item->equipment_images
                ?: ($item->equipment_image ? [$item->equipment_image] : []),
            'specification' => [
                'memory' => $item->spec_memory,
                'storage' => $item->spec_storage,
                'operatingSystem' => $item->spec_operating_system,
                'networkAddress' => $item->spec_network_address,
                'accessories' => $item->spec_accessories,
            ],
            'locationAssignment' => [
                'assignedTo' => $item->location_assigned_to,
                'officeDivision' => $item->location_office_division,
                'dateIssued' => optional($item->location_date_issued)->format('Y-m-d'),
            ],
            'requestHistory' => [
                'natureOfRequest' => $item->request_nature,
                'date' => optional($item->request_date)->format('Y-m-d'),
                'actionTaken' => $item->request_action_taken,
                'assignedStaff' => $item->request_assigned_staff,
                'remarks' => $item->request_remarks,
            ],
            'scheduledMaintenance' => [
                'date' => optional($item->maintenance_date)->format('Y-m-d'),
                'remarks' => $item->maintenance_remarks,
            ],
            'archivedAt' => $status === 'archived'
                ? optional($item->archived_at)->toDateTimeString()
                : null,
        ];
    }
}

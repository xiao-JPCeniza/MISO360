<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\ScanAssignRequest;
use App\Http\Requests\ScanReviewRequest;
use App\Models\TicketArchive;
use App\Models\TicketEnrollment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ScanController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('ScanQr');
    }

    public function lookup(string $uniqueId): JsonResponse
    {
        $uniqueId = strtoupper(trim($uniqueId));

        if (! preg_match('/^MIS-UID-\d{5}$/', $uniqueId)) {
            return response()->json([
                'exists' => false,
                'message' => 'Invalid UID format.',
            ], 422);
        }

        $exists = TicketEnrollment::where('unique_id', $uniqueId)->exists()
            || TicketArchive::where('unique_id', $uniqueId)->exists();

        if (! $exists) {
            return response()->json([
                'exists' => false,
                'message' => 'No record found for this UID.',
            ], 404);
        }

        return response()->json([
            'exists' => true,
            'redirect' => route('scan.show', ['uniqueId' => $uniqueId]),
        ]);
    }

    public function show(Request $request, string $uniqueId): Response
    {
        $uniqueId = strtoupper(trim($uniqueId));

        $enrollment = TicketEnrollment::with('assignedAdmin')
            ->where('unique_id', $uniqueId)
            ->first();

        if ($enrollment) {
            $this->authorize('view', $enrollment);

            $canReview = Gate::allows('review', $enrollment);
            $canAssign = Gate::allows('assign', $enrollment);

            return Inertia::render('ScanResult', [
                'item' => $this->mapDetail($enrollment, 'active'),
                'status' => 'active',
                'canReview' => $canReview,
                'canAssign' => $canAssign,
                'availableAdmins' => $canAssign ? $this->adminOptions() : [],
            ]);
        }

        $archived = TicketArchive::where('unique_id', $uniqueId)
            ->orderByDesc('archived_at')
            ->firstOrFail();

        return Inertia::render('ScanResult', [
            'item' => $this->mapDetail($archived, 'archived'),
            'status' => 'archived',
            'canReview' => false,
            'canAssign' => false,
            'availableAdmins' => [],
        ]);
    }

    public function review(ScanReviewRequest $request, string $uniqueId)
    {
        $uniqueId = strtoupper(trim($uniqueId));
        $enrollment = TicketEnrollment::where('unique_id', $uniqueId)->firstOrFail();
        $this->authorize('review', $enrollment);

        $enrollment->forceFill([
            'repair_status' => 'accepted',
            'repair_comments' => $request->string('comments')->toString(),
            'accepted_for_repair_at' => now(),
        ])->save();

        return redirect()
            ->route('scan.show', ['uniqueId' => $uniqueId])
            ->with('success', 'Record marked as accepted for repair.');
    }

    public function assign(ScanAssignRequest $request, string $uniqueId)
    {
        $uniqueId = strtoupper(trim($uniqueId));
        $enrollment = TicketEnrollment::where('unique_id', $uniqueId)->firstOrFail();
        $this->authorize('assign', $enrollment);

        $enrollment->forceFill([
            'assigned_admin_id' => $request->input('assignedAdminId'),
        ])->save();

        return redirect()
            ->route('scan.show', ['uniqueId' => $uniqueId])
            ->with('success', 'Assignment updated.');
    }

    private function mapDetail(object $item, string $status): array
    {
        $assignedAdmin = $item->assignedAdmin ?? null;

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
            'repairStatus' => $item->repair_status ?? null,
            'repairComments' => $item->repair_comments ?? null,
            'acceptedForRepairAt' => $item->accepted_for_repair_at?->toDateTimeString(),
            'assignedAdmin' => $assignedAdmin
                ? [
                    'id' => $assignedAdmin->id,
                    'name' => $assignedAdmin->name,
                    'email' => $assignedAdmin->email,
                ]
                : null,
        ];
    }

    private function adminOptions(): array
    {
        return User::query()
            ->where('role', Role::ADMIN->value)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->all();
    }
}

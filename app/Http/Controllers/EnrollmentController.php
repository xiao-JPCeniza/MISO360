<?php

namespace App\Http\Controllers;

use App\Models\NatureOfRequest;
use App\Models\TicketArchive;
use App\Models\TicketEnrollment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class EnrollmentController extends Controller
{
    public function create(Request $request)
    {
        return Inertia::render('TicketEnrollment', [
            'mode' => 'create',
            'prefillId' => $request->string('unique_id')->upper()->toString(),
            'natureOfRequests' => $this->getNatureOfRequestOptions(),
        ]);
    }

    public function edit(string $uniqueId)
    {
        $enrollment = TicketEnrollment::where(
            'unique_id',
            strtoupper(trim($uniqueId)),
        )->firstOrFail();

        return Inertia::render('TicketEnrollment', [
            'mode' => 'edit',
            'record' => $this->mapToPayload($enrollment),
            'natureOfRequests' => $this->getNatureOfRequestOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request, true);
        $payload = $this->mapToModelData($validated, $request);

        $duplicate = TicketEnrollment::where('unique_id', $payload['unique_id'])->exists()
            || TicketArchive::where('unique_id', $payload['unique_id'])->exists();

        if ($duplicate) {
            return back()->withErrors([
                'uniqueId' => 'This Unique ID is already registered.',
            ]);
        }

        $enrollment = TicketEnrollment::create($payload);

        return redirect()->route('inventory.show', ['uniqueId' => $enrollment->unique_id]);
    }

    public function update(Request $request, string $uniqueId)
    {
        $enrollment = TicketEnrollment::where(
            'unique_id',
            strtoupper(trim($uniqueId)),
        )->firstOrFail();
        $validated = $this->validatePayload(
            $request,
            false,
            $enrollment->id,
            $enrollment->request_nature,
        );
        $payload = $this->mapToModelData($validated, $request);

        $enrollment->update($payload);

        return redirect()->route('inventory.show', ['uniqueId' => $enrollment->unique_id]);
    }

    private function validatePayload(
        Request $request,
        bool $isCreate,
        ?int $ignoreId = null,
        ?string $existingRequestNature = null,
    ): array {
        $uniqueRule = $isCreate
            ? Rule::unique('ticket_enrollments', 'unique_id')
            : Rule::unique('ticket_enrollments', 'unique_id')->ignore($ignoreId);

        $uniqueIdRules = [
            'required',
            'string',
            'max:255',
            'regex:/^MIS-UID-\\d{5}$/',
            $uniqueRule,
            Rule::exists('issued_uids', 'uid'),
        ];

        // For updates, also ensure the UID doesn't exist in archives
        if (! $isCreate) {
            $uniqueIdRules[] = Rule::unique('ticket_archives', 'unique_id');
        }

        $validated = $request->validate([
            'uniqueId' => $uniqueIdRules,
            'equipmentName' => ['required', 'string', 'max:255'],
            'equipmentType' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'serialNumber' => ['nullable', 'string', 'max:255'],
            'assetTag' => ['nullable', 'string', 'max:255'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'purchaseDate' => ['nullable', 'date'],
            'expiryDate' => ['nullable', 'date'],
            'warrantyStatus' => ['nullable', 'string', 'max:255'],
            'equipmentImageUrls' => ['nullable', 'array'],
            'equipmentImageUrls.*' => ['string', 'max:2048'],
            'equipmentImages' => ['nullable', 'array'],
            'equipmentImages.*' => ['file', 'image', 'max:5120'],
            'specification.memory' => ['nullable', 'string', 'max:255'],
            'specification.storage' => ['nullable', 'string', 'max:255'],
            'specification.operatingSystem' => ['nullable', 'string', 'max:255'],
            'specification.networkAddress' => ['nullable', 'string', 'max:255'],
            'specification.accessories' => ['nullable', 'string', 'max:255'],
            'locationAssignment.assignedTo' => ['nullable', 'string', 'max:255'],
            'locationAssignment.officeDivision' => ['nullable', 'string', 'max:255'],
            'locationAssignment.dateIssued' => ['nullable', 'date'],
            'requestHistory.natureOfRequest' => ['nullable', 'string', 'max:255'],
            'requestHistory.date' => ['nullable', 'date'],
            'requestHistory.actionTaken' => ['nullable', 'string', 'max:255'],
            'requestHistory.assignedStaff' => ['nullable', 'string', 'max:255'],
            'requestHistory.remarks' => ['nullable', 'string', 'max:255'],
            'scheduledMaintenance.date' => ['nullable', 'date'],
            'scheduledMaintenance.remarks' => ['nullable', 'string', 'max:255'],
        ], [
            'uniqueId.exists' => 'Invalid UID: This QR code was not generated by the system.',
            'uniqueId.unique' => 'This Unique ID is already archived and cannot be reused.',
        ]);

        $natureOfRequest = data_get($validated, 'requestHistory.natureOfRequest');

        if ($natureOfRequest) {
            $isCurrentValue = $existingRequestNature && $natureOfRequest === $existingRequestNature;
            $isValid = NatureOfRequest::query()
                ->where('is_active', true)
                ->where('name', $natureOfRequest)
                ->exists();

            if (! $isCurrentValue && ! $isValid) {
                throw ValidationException::withMessages([
                    'requestHistory.natureOfRequest' => 'Please select a valid request type.',
                ]);
            }
        }

        return $validated;
    }

    private function getNatureOfRequestOptions(): array
    {
        return NatureOfRequest::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (NatureOfRequest $request) => [
                'id' => $request->id,
                'name' => $request->name,
            ])
            ->values()
            ->toArray();
    }

    private function mapToModelData(array $data, Request $request): array
    {
        $existingImages = array_values($data['equipmentImageUrls'] ?? []);
        $uploadedImages = [];

        if ($request->hasFile('equipmentImages')) {
            foreach ($request->file('equipmentImages', []) as $file) {
                $uploadedImages[] = $file->store('inventory', 'public');
            }
        }

        $allImages = array_values(
            array_filter(array_merge($existingImages, $uploadedImages)),
        );

        return [
            'unique_id' => strtoupper(trim($data['uniqueId'])),
            'equipment_name' => $data['equipmentName'],
            'equipment_type' => $data['equipmentType'] ?? null,
            'brand' => $data['brand'] ?? null,
            'model' => $data['model'] ?? null,
            'serial_number' => $data['serialNumber'] ?? null,
            'asset_tag' => $data['assetTag'] ?? null,
            'supplier' => $data['supplier'] ?? null,
            'purchase_date' => $data['purchaseDate'] ?? null,
            'expiry_date' => $data['expiryDate'] ?? null,
            'warranty_status' => $data['warrantyStatus'] ?? null,
            'equipment_image' => $allImages[0] ?? null,
            'equipment_images' => $allImages,
            'spec_memory' => data_get($data, 'specification.memory'),
            'spec_storage' => data_get($data, 'specification.storage'),
            'spec_operating_system' => data_get($data, 'specification.operatingSystem'),
            'spec_network_address' => data_get($data, 'specification.networkAddress'),
            'spec_accessories' => data_get($data, 'specification.accessories'),
            'location_assigned_to' => data_get($data, 'locationAssignment.assignedTo'),
            'location_office_division' => data_get($data, 'locationAssignment.officeDivision'),
            'location_date_issued' => data_get($data, 'locationAssignment.dateIssued'),
            'request_nature' => data_get($data, 'requestHistory.natureOfRequest'),
            'request_date' => data_get($data, 'requestHistory.date'),
            'request_action_taken' => data_get($data, 'requestHistory.actionTaken'),
            'request_assigned_staff' => data_get($data, 'requestHistory.assignedStaff'),
            'request_remarks' => data_get($data, 'requestHistory.remarks'),
            'maintenance_date' => data_get($data, 'scheduledMaintenance.date'),
            'maintenance_remarks' => data_get($data, 'scheduledMaintenance.remarks'),
        ];
    }

    private function mapToPayload(TicketEnrollment $enrollment): array
    {
        return [
            'uniqueId' => $enrollment->unique_id,
            'equipmentName' => $enrollment->equipment_name,
            'equipmentType' => $enrollment->equipment_type,
            'brand' => $enrollment->brand,
            'model' => $enrollment->model,
            'serialNumber' => $enrollment->serial_number,
            'assetTag' => $enrollment->asset_tag,
            'supplier' => $enrollment->supplier,
            'purchaseDate' => optional($enrollment->purchase_date)->format('Y-m-d'),
            'expiryDate' => optional($enrollment->expiry_date)->format('Y-m-d'),
            'warrantyStatus' => $enrollment->warranty_status,
            'equipmentImageUrls' => $enrollment->equipment_images
                ?: ($enrollment->equipment_image ? [$enrollment->equipment_image] : []),
            'specification' => [
                'memory' => $enrollment->spec_memory,
                'storage' => $enrollment->spec_storage,
                'operatingSystem' => $enrollment->spec_operating_system,
                'networkAddress' => $enrollment->spec_network_address,
                'accessories' => $enrollment->spec_accessories,
            ],
            'locationAssignment' => [
                'assignedTo' => $enrollment->location_assigned_to,
                'officeDivision' => $enrollment->location_office_division,
                'dateIssued' => optional($enrollment->location_date_issued)->format('Y-m-d'),
            ],
            'requestHistory' => [
                'natureOfRequest' => $enrollment->request_nature,
                'date' => optional($enrollment->request_date)->format('Y-m-d'),
                'actionTaken' => $enrollment->request_action_taken,
                'assignedStaff' => $enrollment->request_assigned_staff,
                'remarks' => $enrollment->request_remarks,
            ],
            'scheduledMaintenance' => [
                'date' => optional($enrollment->maintenance_date)->format('Y-m-d'),
                'remarks' => $enrollment->maintenance_remarks,
            ],
        ];
    }
}

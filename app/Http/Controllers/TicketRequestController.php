<?php

namespace App\Http\Controllers;

use App\Enums\ReferenceValueGroup;
use App\Http\Requests\StoreTicketRequestRequest;
use App\Models\NatureOfRequest;
use App\Models\ReferenceValue;
use App\Models\TicketRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TicketRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isAdmin = $user?->isAdmin() ?? false;

        $ticketRequests = TicketRequest::query()
            ->with([
                'natureOfRequest:id,name',
                'officeDesignation:id,name',
                'status:id,name',
                'category:id,name',
                'requestedForUser:id,name,position_title',
                'user:id,name,position_title',
            ])
            ->when(! $isAdmin && $user, fn ($query) => $query->where('user_id', $user->id))
            ->latest()
            ->get()
            ->map(fn (TicketRequest $ticketRequest) => [
                'id' => $ticketRequest->id,
                'controlTicketNumber' => $ticketRequest->control_ticket_number,
                'requestedBy' => $ticketRequest->requestedForUser?->name ?? $ticketRequest->user?->name,
                'positionTitle' => $ticketRequest->requestedForUser?->position_title
                    ?? $ticketRequest->user?->position_title,
                'office' => $ticketRequest->officeDesignation?->name,
                'dateFiled' => $ticketRequest->created_at?->toDateString(),
                'natureOfRequest' => $ticketRequest->natureOfRequest?->name,
                'requestDescription' => $ticketRequest->description,
                'remarks' => null,
                'assignedStaff' => null,
                'status' => $ticketRequest->status?->name,
                'category' => $ticketRequest->category?->name,
                'estimatedCompletionDate' => null,
                'showUrl' => route('requests.show', $ticketRequest),
            ]);

        return Inertia::render('requests/Requests', [
            'requests' => $ticketRequests,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user?->isAdmin() ?? false;
        $user?->load('officeDesignation');

        $natureOfRequests = NatureOfRequest::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $preSelectedNatureId = $this->resolveServiceToNatureId(
            $request->string('service')->trim()->toString(),
            $natureOfRequests,
        );

        return Inertia::render('requests/SubmitRequest', [
            'controlTicketNumber' => $this->generateControlTicketNumber(),
            'natureOfRequests' => $natureOfRequests,
            'preSelectedNatureId' => $preSelectedNatureId,
            'isAdmin' => $isAdmin,
            'officeOptions' => $isAdmin
                ? ReferenceValue::query()
                    ->forGroup(ReferenceValueGroup::OfficeDesignation)
                    ->active()
                    ->orderBy('name')
                    ->get(['id', 'name'])
                : [],
            'officeUsers' => $isAdmin
                ? User::query()
                    ->where('is_active', true)
                    ->whereNotNull('office_designation_id')
                    ->orderBy('name')
                    ->get(['id', 'name', 'office_designation_id'])
                : [],
            'maxAttachments' => 5,
            'maxAttachmentSizeMb' => 10,
            'qrCodePattern' => '^MIS-UID-\\d{5}$',
        ]);
    }

    /**
     * Resolve a service name (from Services Hub) to an active nature-of-request ID.
     * Uses case-insensitive match so hub labels and DB names can differ slightly.
     */
    private function resolveServiceToNatureId(string $serviceName, \Illuminate\Support\Collection $natureOfRequests): ?int
    {
        if ($serviceName === '') {
            return null;
        }

        $normalized = strtolower(trim($serviceName));

        foreach ($natureOfRequests as $nature) {
            if (strtolower(trim($nature->name)) === $normalized) {
                return $nature->id;
            }
        }

        return null;
    }

    public function store(StoreTicketRequestRequest $request)
    {
        $validated = $request->validated();
        $attachments = [];

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments', []) as $file) {
                $path = $file->store('ticket-requests', 'public');
                $attachments[] = [
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                ];
            }
        }

        $requester = $request->user();
        $isAdmin = $requester->isAdmin();
        $requestedForUserId = $isAdmin
            ? $validated['requestedForUserId']
            : $requester->id;
        $officeDesignationId = $isAdmin
            ? $validated['officeDesignationId']
            : $requester->office_designation_id;

        $ticketRequest = TicketRequest::create([
            'control_ticket_number' => $this->resolveControlTicketNumber(
                $validated['controlTicketNumber'] ?? null,
            ),
            'nature_of_request_id' => $validated['natureOfRequestId'],
            'description' => $validated['description'],
            'has_qr_code' => $validated['hasQrCode'],
            'qr_code_number' => $validated['hasQrCode']
                ? strtoupper(trim($validated['qrCodeNumber']))
                : null,
            'attachments' => $attachments ?: null,
            'user_id' => $requester->id,
            'requested_for_user_id' => $requestedForUserId,
            'office_designation_id' => $officeDesignationId,
        ]);

        return redirect()->route('requests.show', $ticketRequest);
    }

    public function show(Request $request, TicketRequest $ticketRequest)
    {
        if (! $request->user()->isAdmin() && $ticketRequest->user_id !== $request->user()->id) {
            abort(403);
        }

        $ticketRequest->load('natureOfRequest');

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return Inertia::render('requests/TicketRequestConfirmation', [
            'ticket' => [
                'controlTicketNumber' => $ticketRequest->control_ticket_number,
                'natureOfRequest' => $ticketRequest->natureOfRequest?->name,
                'description' => $ticketRequest->description,
                'hasQrCode' => $ticketRequest->has_qr_code,
                'qrCodeNumber' => $ticketRequest->qr_code_number,
                'attachments' => collect($ticketRequest->attachments ?? [])
                    ->map(fn (array $attachment) => [
                        'name' => $attachment['name'] ?? basename($attachment['path'] ?? ''),
                        'size' => $attachment['size'] ?? null,
                        'mime' => $attachment['mime'] ?? null,
                        'url' => isset($attachment['path'])
                            ? $disk->url($attachment['path'])
                            : null,
                    ])
                    ->values(),
            ],
        ]);
    }

    public function itGovernance(Request $request, TicketRequest $ticketRequest)
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        $ticketRequest->load(['natureOfRequest', 'officeDesignation', 'status', 'category', 'requestedForUser', 'user']);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        $requestedByUser = $ticketRequest->requestedForUser ?? $ticketRequest->user;

        $ticket = [
            'controlTicketNumber' => $ticketRequest->control_ticket_number,
            'requestedBy' => $requestedByUser?->name,
            'positionTitle' => $requestedByUser?->position_title,
            'office' => $ticketRequest->officeDesignation?->name,
            'dateFiled' => $ticketRequest->created_at?->toDateString(),
            'email' => $requestedByUser?->email,
            'natureOfRequest' => $ticketRequest->natureOfRequest?->name,
            'requestDescription' => $ticketRequest->description,
            'attachments' => collect($ticketRequest->attachments ?? [])
                ->map(fn (array $attachment) => [
                    'name' => $attachment['name'] ?? basename($attachment['path'] ?? ''),
                    'url' => isset($attachment['path']) ? $disk->url($attachment['path']) : null,
                ])
                ->values()
                ->all(),
            'remarksId' => null,
            'assignedStaffId' => null,
            'dateReceived' => null,
            'dateStarted' => null,
            'estimatedCompletionDate' => null,
            'actionTaken' => null,
            'categoryId' => $ticketRequest->category_id,
            'statusId' => $ticketRequest->status_id,
        ];

        $staffOptions = User::query()
            ->where('is_active', true)
            ->whereIn('role', [\App\Enums\Role::ADMIN, \App\Enums\Role::SUPER_ADMIN])
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name])
            ->values()
            ->all();

        $remarksOptions = ReferenceValue::query()
            ->forGroup(ReferenceValueGroup::Remarks)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ReferenceValue $rv) => ['id' => $rv->id, 'name' => $rv->name])
            ->values()
            ->all();

        $categoryOptions = ReferenceValue::query()
            ->forGroup(ReferenceValueGroup::Category)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ReferenceValue $rv) => ['id' => $rv->id, 'name' => $rv->name])
            ->values()
            ->all();

        $statusOptions = ReferenceValue::query()
            ->forGroup(ReferenceValueGroup::Status)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ReferenceValue $rv) => ['id' => $rv->id, 'name' => $rv->name])
            ->values()
            ->all();

        return Inertia::render('requests/ItGovernanceRequest', [
            'ticket' => $ticket,
            'updateUrl' => route('requests.it-governance.update', $ticketRequest),
            'staffOptions' => $staffOptions,
            'remarksOptions' => $remarksOptions,
            'categoryOptions' => $categoryOptions,
            'statusOptions' => $statusOptions,
            'canEdit' => true,
        ]);
    }

    public function updateItGovernance(Request $request, TicketRequest $ticketRequest)
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'remarksId' => ['nullable', 'string'],
            'assignedStaffId' => ['nullable', 'string'],
            'dateReceived' => ['nullable', 'date'],
            'dateStarted' => ['nullable', 'date'],
            'estimatedCompletionDate' => ['nullable', 'date'],
            'actionTaken' => ['nullable', 'string', 'max:500'],
            'categoryId' => ['nullable', 'integer'],
            'statusId' => ['nullable', 'integer'],
        ]);

        $ticketRequest->update([
            'status_id' => $validated['statusId'] ?? null,
            'category_id' => $validated['categoryId'] ?? null,
        ]);

        return redirect()->route('requests.it-governance.show', $ticketRequest)
            ->with('success', 'IT Governance request updated successfully.');
    }

    public function equipmentAndNetwork(Request $request, TicketRequest $ticketRequest)
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        $ticketRequest->load(['natureOfRequest', 'officeDesignation', 'status', 'category', 'requestedForUser', 'user']);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        $requestedByUser = $ticketRequest->requestedForUser ?? $ticketRequest->user;

        $ticket = [
            'controlTicketNumber' => $ticketRequest->control_ticket_number,
            'requestedBy' => $requestedByUser?->name,
            'positionTitle' => $requestedByUser?->position_title,
            'office' => $ticketRequest->officeDesignation?->name,
            'dateFiled' => $ticketRequest->created_at?->toDateString(),
            'email' => $requestedByUser?->email,
            'natureOfRequest' => $ticketRequest->natureOfRequest?->name,
            'requestDescription' => $ticketRequest->description,
            'attachments' => collect($ticketRequest->attachments ?? [])
                ->map(fn (array $attachment) => [
                    'name' => $attachment['name'] ?? basename($attachment['path'] ?? ''),
                    'url' => isset($attachment['path']) ? $disk->url($attachment['path']) : null,
                ])
                ->values()
                ->all(),
            'remarksId' => null,
            'assignedStaffId' => null,
            'dateReceived' => null,
            'dateStarted' => null,
            'estimatedCompletionDate' => null,
            'actionTaken' => null,
            'categoryId' => $ticketRequest->category_id,
            'statusId' => $ticketRequest->status_id,
        ];

        $staffOptions = User::query()
            ->where('is_active', true)
            ->whereIn('role', [\App\Enums\Role::ADMIN, \App\Enums\Role::SUPER_ADMIN])
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name])
            ->values()
            ->all();

        $remarksOptions = ReferenceValue::query()
            ->forGroup(ReferenceValueGroup::Remarks)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ReferenceValue $rv) => ['id' => $rv->id, 'name' => $rv->name])
            ->values()
            ->all();

        $categoryOptions = ReferenceValue::query()
            ->forGroup(ReferenceValueGroup::Category)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ReferenceValue $rv) => ['id' => $rv->id, 'name' => $rv->name])
            ->values()
            ->all();

        $statusOptions = ReferenceValue::query()
            ->forGroup(ReferenceValueGroup::Status)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ReferenceValue $rv) => ['id' => $rv->id, 'name' => $rv->name])
            ->values()
            ->all();

        return Inertia::render('requests/EquipmentAndNetwork', [
            'ticket' => $ticket,
            'updateUrl' => route('requests.equipment-network.update', $ticketRequest),
            'staffOptions' => $staffOptions,
            'remarksOptions' => $remarksOptions,
            'categoryOptions' => $categoryOptions,
            'statusOptions' => $statusOptions,
            'canEdit' => true,
        ]);
    }

    public function updateEquipmentAndNetwork(Request $request, TicketRequest $ticketRequest)
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'remarksId' => ['nullable', 'string'],
            'assignedStaffId' => ['nullable', 'string'],
            'dateReceived' => ['nullable', 'date'],
            'dateStarted' => ['nullable', 'date'],
            'estimatedCompletionDate' => ['nullable', 'date'],
            'actionTaken' => ['nullable', 'string', 'max:500'],
            'categoryId' => ['nullable', 'integer'],
            'statusId' => ['nullable', 'integer'],
        ]);

        $ticketRequest->update([
            'status_id' => $validated['statusId'] ?? null,
            'category_id' => $validated['categoryId'] ?? null,
        ]);

        return redirect()->route('requests.equipment-network.show', $ticketRequest)
            ->with('success', 'Equipment and network request updated successfully.');
    }

    private function generateControlTicketNumber(): string
    {
        $date = now()->format('Ymd');

        return $this->generateUniqueTicketNumber($date);
    }

    private function resolveControlTicketNumber(?string $provided): string
    {
        $date = now()->format('Ymd');

        if ($provided && preg_match('/^CTN-\d{8}-\d{4}$/', $provided)) {
            $isUnique = ! TicketRequest::query()
                ->where('control_ticket_number', $provided)
                ->exists();

            if ($isUnique) {
                return $provided;
            }
        }

        return $this->generateUniqueTicketNumber($date);
    }

    private function generateUniqueTicketNumber(string $date): string
    {
        do {
            $sequence = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $candidate = "CTN-{$date}-{$sequence}";
        } while (TicketRequest::query()->where('control_ticket_number', $candidate)->exists());

        return $candidate;
    }
}

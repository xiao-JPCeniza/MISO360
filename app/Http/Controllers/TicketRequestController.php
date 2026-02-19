<?php

namespace App\Http\Controllers;

use App\Enums\ReferenceValueGroup;
use App\Http\Requests\StoreTicketRequestRequest;
use App\Models\NatureOfRequest;
use App\Models\ReferenceValue;
use App\Models\TicketRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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
                'remarks:id,name',
                'assignedStaff:id,name',
                'requestedForUser:id,name,position_title',
                'user:id,name,position_title',
            ])
            ->when(! $isAdmin && $user, fn ($query) => $query->where('user_id', $user->id))
            ->notArchived()
            ->orderBy('created_at')
            ->limit(20)
            ->get()
            ->map(fn (TicketRequest $ticketRequest) => $this->mapTicketRequestToRow($ticketRequest));

        return Inertia::render('requests/Requests', [
            'requests' => $ticketRequests,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function archive(Request $request): Response
    {
        $user = $request->user();
        $isAdmin = $user?->isAdmin() ?? false;

        $archivedRequests = TicketRequest::query()
            ->with([
                'natureOfRequest:id,name',
                'officeDesignation:id,name',
                'status:id,name',
                'category:id,name',
                'remarks:id,name',
                'assignedStaff:id,name',
                'requestedForUser:id,name,position_title',
                'user:id,name,position_title',
            ])
            ->when(! $isAdmin && $user, fn ($query) => $query->where('user_id', $user->id))
            ->archived()
            ->latest()
            ->get()
            ->map(fn (TicketRequest $ticketRequest) => $this->mapTicketRequestToRow($ticketRequest));

        return Inertia::render('requests/Archive', [
            'requests' => $archivedRequests,
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapTicketRequestToRow(TicketRequest $ticketRequest): array
    {
        return [
            'id' => $ticketRequest->id,
            'controlTicketNumber' => $ticketRequest->control_ticket_number,
            'requestedBy' => $ticketRequest->requestedForUser?->name ?? $ticketRequest->user?->name,
            'positionTitle' => $ticketRequest->requestedForUser?->position_title
                ?? $ticketRequest->user?->position_title,
            'office' => $ticketRequest->officeDesignation?->name,
            'dateFiled' => $ticketRequest->created_at?->toDateString(),
            'natureOfRequest' => $ticketRequest->natureOfRequest?->name,
            'requestDescription' => $ticketRequest->description,
            'remarks' => $ticketRequest->remarks?->name,
            'assignedStaff' => $ticketRequest->assignedStaff?->name,
            'status' => $ticketRequest->status?->name,
            'category' => $ticketRequest->category?->name,
            'estimatedCompletionDate' => $ticketRequest->estimated_completion_date?->toDateString(),
            'showUrl' => route('requests.show', $ticketRequest),
        ];
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
            'requesterName' => $user?->name,
            'defaultOfficeDivision' => $user?->officeDesignation?->name,
            'systemsEngineerOptions' => User::query()
                ->where('is_active', true)
                ->whereIn('role', [\App\Enums\Role::ADMIN, \App\Enums\Role::SUPER_ADMIN])
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name])
                ->values()
                ->all(),
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

    public function store(StoreTicketRequestRequest $request, AuditLogger $auditLogger)
    {
        $requester = $request->user();
        if (! $requester) {
            abort(401, 'Authentication required to submit a ticket request.');
        }

        $validated = $request->validated();
        $attachments = [];

        if ($request->hasFile('attachments')) {
            $files = Arr::wrap($request->file('attachments', []));
            foreach ($files as $file) {
                if (! $file || ! $file->isValid()) {
                    continue;
                }
                $path = $file->store('ticket-requests', 'public');
                $attachments[] = [
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize() ?: 0,
                    'mime' => $file->getMimeType() ?: 'application/octet-stream',
                ];
            }
        }

        $survey = $validated['systemDevelopmentSurvey'] ?? null;
        $systemChangeRequestForm = $validated['systemChangeRequestForm'] ?? null;
        $systemIssueReport = $validated['systemIssueReport'] ?? null;
        $isAdmin = $requester->isAdmin();
        $resolvedControlTicketNumber = $this->resolveControlTicketNumber(
            $validated['controlTicketNumber'] ?? null,
        );
        $requestedForUserId = $isAdmin
            ? (int) $validated['requestedForUserId']
            : $requester->id;
        $officeDesignationId = $isAdmin
            ? (int) $validated['officeDesignationId']
            : $requester->office_designation_id;

        $officeName = null;
        if ($officeDesignationId) {
            $officeName = ReferenceValue::query()
                ->whereKey($officeDesignationId)
                ->value('name');
        }

        $requestedByName = User::query()
            ->whereKey($requestedForUserId)
            ->value('name');

        if (is_array($survey)) {
            // Office End-User is the office of the requester (not editable).
            if (is_string($officeName) && trim($officeName) !== '') {
                $survey['officeEndUser'] = $officeName;
            }

            // Assigned engineer + target completion are admin-managed.
            if (! $isAdmin) {
                $survey['assignedSystemsEngineer'] = null;
                $survey['targetCompletion'] = null;
            }

            array_unshift($attachments, [
                'type' => 'system_development_survey',
                'payload' => $survey,
            ]);
        }

        if (is_array($systemChangeRequestForm)) {
            // Mirror the header fields to the ticket context (server truth).
            $systemChangeRequestForm['controlNumber'] = $resolvedControlTicketNumber;
            if (is_string($officeName) && trim($officeName) !== '') {
                $systemChangeRequestForm['officeDivision'] = $officeName;
            }
            if (is_string($requestedByName) && trim($requestedByName) !== '') {
                $systemChangeRequestForm['requestedByName'] = $requestedByName;
            }

            if (! $isAdmin) {
                $systemChangeRequestForm['evaluatedBy'] = null;
                $systemChangeRequestForm['approvedBy'] = null;
                $systemChangeRequestForm['notedBy'] = null;
                $systemChangeRequestForm['remarks'] = null;
            }

            array_unshift($attachments, [
                'type' => 'system_change_request_form',
                'payload' => $systemChangeRequestForm,
            ]);
        }

        if (is_array($systemIssueReport)) {
            $systemIssueReport['controlNumber'] = $resolvedControlTicketNumber;
            if (is_string($officeName) && trim($officeName) !== '') {
                $systemIssueReport['requestingDepartment'] = $officeName;
            }
            if (is_string($requestedByName) && trim($requestedByName) !== '') {
                $systemIssueReport['requestingEmployee'] = $requestedByName;
            }
            if (! $isAdmin) {
                $systemIssueReport['reportedBy'] = null;
                $systemIssueReport['reportedByDate'] = null;
                $systemIssueReport['reportedBySignature'] = null;
                $systemIssueReport['acceptedBy'] = null;
                $systemIssueReport['acceptedByDate'] = null;
                $systemIssueReport['acceptedBySignature'] = null;
                $systemIssueReport['evaluatedBy'] = null;
                $systemIssueReport['evaluatedByDate'] = null;
                $systemIssueReport['evaluatedBySignature'] = null;
                $systemIssueReport['approvedBy'] = null;
                $systemIssueReport['approvedByDate'] = null;
                $systemIssueReport['approvedBySignature'] = null;
            }
            array_unshift($attachments, [
                'type' => 'system_issue_report',
                'payload' => $systemIssueReport,
            ]);
        }

        if ($request->hasFile('systemIssueReportAttachments')) {
            $files = Arr::wrap($request->file('systemIssueReportAttachments', []));
            foreach ($files as $file) {
                if (! $file || ! $file->isValid()) {
                    continue;
                }
                $path = $file->store('ticket-requests/system-issue-reports', 'public');
                $attachments[] = [
                    'type' => 'system_issue_report_attachment',
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize() ?: 0,
                    'mime' => $file->getMimeType() ?: 'application/octet-stream',
                ];
            }
        }

        if ($request->hasFile('systemDevelopmentSurveyFormAttachments')) {
            $files = $request->file('systemDevelopmentSurveyFormAttachments', []);
            $files = is_array($files) ? $files : [$files];
            foreach ($files as $index => $file) {
                if (! $file || ! $file->isValid()) {
                    continue;
                }

                $path = $file->store('ticket-requests/system-development-forms', 'public');

                $titleOfForm = null;
                $description = null;

                if (is_array($survey)) {
                    $forms = $survey['forms'] ?? null;
                    if (is_array($forms) && isset($forms[$index]) && is_array($forms[$index])) {
                        $titleOfForm = $forms[$index]['titleOfForm'] ?? null;
                        $description = $forms[$index]['description'] ?? null;
                    }
                }

                $attachments[] = [
                    'type' => 'system_development_form_attachment',
                    'formIndex' => (int) $index,
                    'titleOfForm' => is_string($titleOfForm) ? $titleOfForm : null,
                    'description' => is_string($description) ? $description : null,
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize() ?: 0,
                    'mime' => $file->getMimeType() ?: 'application/octet-stream',
                ];
            }
        }

        $ticketRequest = TicketRequest::create([
            'control_ticket_number' => $resolvedControlTicketNumber,
            'nature_of_request_id' => (int) $validated['natureOfRequestId'],
            'description' => $validated['description'],
            'has_qr_code' => (bool) $validated['hasQrCode'],
            'qr_code_number' => $validated['hasQrCode']
                ? strtoupper(trim((string) $validated['qrCodeNumber']))
                : null,
            'attachments' => $attachments ?: null,
            'user_id' => $requester->id,
            'requested_for_user_id' => $requestedForUserId,
            'office_designation_id' => $officeDesignationId !== null && $officeDesignationId !== '' ? (int) $officeDesignationId : null,
        ]);

        if (is_array($systemIssueReport)) {
            $auditLogger->log($request, 'ticket_request.system_issue_report.submitted', $ticketRequest, [
                'control_ticket_number' => $ticketRequest->control_ticket_number,
            ]);
        }

        return redirect()->route('requests.show', $ticketRequest);
    }

    public function show(Request $request, TicketRequest $ticketRequest)
    {
        $user = $request->user();
        if (! $user) {
            abort(401, 'Authentication required.');
        }
        if (! $user->isAdmin() && $ticketRequest->user_id !== $user->id && $ticketRequest->requested_for_user_id !== $user->id) {
            abort(403, 'You do not have permission to view this request.');
        }

        $ticketRequest->load('natureOfRequest');

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        [$fileAttachments, $systemDevelopmentSurvey, $systemChangeRequestForm, $systemIssueReport, $issueReportAttachments] = $this->splitAttachments(is_array($ticketRequest->attachments) ? $ticketRequest->attachments : []);

        return Inertia::render('requests/TicketRequestConfirmation', [
            'ticket' => [
                'controlTicketNumber' => $ticketRequest->control_ticket_number,
                'natureOfRequest' => $ticketRequest->natureOfRequest?->name,
                'description' => $ticketRequest->description,
                'hasQrCode' => $ticketRequest->has_qr_code,
                'qrCodeNumber' => $ticketRequest->qr_code_number,
                'attachments' => collect($fileAttachments)
                    ->map(fn (array $attachment) => [
                        'name' => $attachment['name'] ?? basename($attachment['path'] ?? ''),
                        'size' => $attachment['size'] ?? null,
                        'mime' => $attachment['mime'] ?? null,
                        'url' => isset($attachment['path'])
                            ? $disk->url($attachment['path'])
                            : null,
                    ])
                    ->values(),
                'systemDevelopmentSurvey' => $systemDevelopmentSurvey,
                'systemChangeRequestForm' => $systemChangeRequestForm,
                'systemIssueReport' => $systemIssueReport,
                'systemIssueReportAttachments' => collect($issueReportAttachments)
                    ->map(fn (array $a) => [
                        'name' => $a['name'] ?? basename($a['path'] ?? ''),
                        'size' => $a['size'] ?? null,
                        'mime' => $a['mime'] ?? null,
                        'url' => isset($a['path']) ? $disk->url($a['path']) : null,
                    ])
                    ->values()
                    ->all(),
            ],
        ]);
    }

    public function itGovernance(Request $request, TicketRequest $ticketRequest)
    {
        // Access restricted to admin/super_admin via middleware.

        $ticketRequest->load(['natureOfRequest', 'officeDesignation', 'status', 'category', 'remarks', 'assignedStaff', 'requestedForUser', 'user']);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        $requestedByUser = $ticketRequest->requestedForUser ?? $ticketRequest->user;
        [$fileAttachments, $systemDevelopmentSurvey, $systemChangeRequestForm, $systemIssueReport, $issueReportAttachments] = $this->splitAttachments(is_array($ticketRequest->attachments) ? $ticketRequest->attachments : []);

        $ticket = [
            'controlTicketNumber' => $ticketRequest->control_ticket_number,
            'requestedBy' => $requestedByUser?->name,
            'positionTitle' => $requestedByUser?->position_title,
            'office' => $ticketRequest->officeDesignation?->name,
            'dateFiled' => $ticketRequest->created_at?->toDateString(),
            'email' => $requestedByUser?->email,
            'natureOfRequest' => $ticketRequest->natureOfRequest?->name,
            'natureOfRequestId' => $ticketRequest->nature_of_request_id != null ? (string) $ticketRequest->nature_of_request_id : null,
            'requestDescription' => $ticketRequest->description,
            'attachments' => collect($fileAttachments)
                ->map(fn (array $attachment) => [
                    'name' => $attachment['name'] ?? basename($attachment['path'] ?? ''),
                    'url' => isset($attachment['path']) ? $disk->url($attachment['path']) : null,
                ])
                ->values()
                ->all(),
            'systemDevelopmentSurvey' => $systemDevelopmentSurvey,
            'systemChangeRequestForm' => $systemChangeRequestForm,
            'systemIssueReport' => $systemIssueReport,
            'systemIssueReportAttachments' => collect($issueReportAttachments)
                ->map(fn (array $a) => [
                    'name' => $a['name'] ?? basename($a['path'] ?? ''),
                    'url' => isset($a['path']) ? $disk->url($a['path']) : null,
                ])
                ->values()
                ->all(),
            'remarksId' => $ticketRequest->remarks_id != null ? (string) $ticketRequest->remarks_id : null,
            'assignedStaffId' => $ticketRequest->assigned_staff_id != null ? (string) $ticketRequest->assigned_staff_id : null,
            'dateReceived' => $ticketRequest->date_received?->toDateString(),
            'dateStarted' => $ticketRequest->date_started?->toDateString(),
            'estimatedCompletionDate' => $ticketRequest->estimated_completion_date?->toDateString(),
            'actionTaken' => $ticketRequest->action_taken,
            'categoryId' => $ticketRequest->category_id,
            'statusId' => $ticketRequest->status_id,
            'equipmentNetworkDetails' => $ticketRequest->equipment_network_details ?? [],
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

        $natureOfRequests = NatureOfRequest::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (NatureOfRequest $n) => ['id' => $n->id, 'name' => $n->name])
            ->values()
            ->all();

        return Inertia::render('requests/ItGovernanceRequest', [
            'ticket' => $ticket,
            'updateUrl' => route('requests.it-governance.update', $ticketRequest),
            'natureOfRequests' => $natureOfRequests,
            'staffOptions' => $staffOptions,
            'remarksOptions' => $remarksOptions,
            'categoryOptions' => $categoryOptions,
            'statusOptions' => $statusOptions,
            'canEdit' => true,
        ]);
    }

    public function updateItGovernance(Request $request, TicketRequest $ticketRequest, AuditLogger $auditLogger)
    {
        // Access restricted to admin/super_admin via middleware.

        $validated = $request->validate([
            'natureOfRequestId' => ['nullable', 'integer', 'exists:nature_of_requests,id'],
            'remarksId' => ['nullable', 'string'],
            'assignedStaffId' => ['nullable', 'string'],
            'dateReceived' => ['nullable', 'date'],
            'dateStarted' => ['nullable', 'date'],
            'estimatedCompletionDate' => ['nullable', 'date'],
            'actionTaken' => ['nullable', 'string', 'max:500'],
            'categoryId' => ['nullable', 'integer'],
            'statusId' => ['nullable', 'integer'],
            'systemDevelopmentSurvey' => ['nullable', 'array'],
            'systemDevelopmentSurvey.targetCompletion' => ['nullable', 'date'],
            'systemDevelopmentSurvey.assignedSystemsEngineer' => ['nullable', 'string', 'max:255'],
            'systemChangeRequestForm' => ['nullable', 'array'],
            'systemChangeRequestForm.evaluatedBy' => ['nullable', 'string', 'max:255'],
            'systemChangeRequestForm.approvedBy' => ['nullable', 'string', 'max:255'],
            'systemChangeRequestForm.notedBy' => ['nullable', 'string', 'max:255'],
            'systemChangeRequestForm.remarks' => ['nullable', 'string', 'max:2000'],
            'systemIssueReport' => ['nullable', 'array'],
            'systemIssueReport.reportedBy' => ['nullable', 'string', 'max:255'],
            'systemIssueReport.reportedByDate' => ['nullable', 'string', 'max:50'],
            'systemIssueReport.reportedBySignature' => ['nullable', 'string', 'max:255'],
            'systemIssueReport.acceptedBy' => ['nullable', 'string', 'max:255'],
            'systemIssueReport.acceptedByDate' => ['nullable', 'string', 'max:50'],
            'systemIssueReport.acceptedBySignature' => ['nullable', 'string', 'max:255'],
            'systemIssueReport.evaluatedBy' => ['nullable', 'string', 'max:255'],
            'systemIssueReport.evaluatedByDate' => ['nullable', 'string', 'max:50'],
            'systemIssueReport.evaluatedBySignature' => ['nullable', 'string', 'max:255'],
            'systemIssueReport.approvedBy' => ['nullable', 'string', 'max:255'],
            'systemIssueReport.approvedByDate' => ['nullable', 'string', 'max:50'],
            'systemIssueReport.approvedBySignature' => ['nullable', 'string', 'max:255'],
        ]);

        $this->validateRequestDateOrder($validated, 'dateReceived', 'dateStarted', 'estimatedCompletionDate');

        $remarksId = isset($validated['remarksId']) && $validated['remarksId'] !== '' ? (int) $validated['remarksId'] : null;
        $assignedStaffId = isset($validated['assignedStaffId']) && $validated['assignedStaffId'] !== '' ? (int) $validated['assignedStaffId'] : null;
        $natureOfRequestId = array_key_exists('natureOfRequestId', $validated)
            ? (isset($validated['natureOfRequestId']) && $validated['natureOfRequestId'] !== '' ? (int) $validated['natureOfRequestId'] : null)
            : $ticketRequest->nature_of_request_id;

        $statusId = array_key_exists('statusId', $validated) && $validated['statusId'] !== '' && $validated['statusId'] !== null
            ? (int) $validated['statusId']
            : $ticketRequest->status_id;
        $isCompleted = $statusId && ReferenceValue::query()
            ->forGroup(ReferenceValueGroup::Status)
            ->where('id', $statusId)
            ->where('name', 'Completed')
            ->exists();

        $updatePayload = [
            'nature_of_request_id' => $natureOfRequestId,
            'remarks_id' => $remarksId,
            'assigned_staff_id' => $assignedStaffId,
            'date_received' => $validated['dateReceived'] ?? null,
            'date_started' => $validated['dateStarted'] ?? null,
            'estimated_completion_date' => $validated['estimatedCompletionDate'] ?? null,
            'action_taken' => $validated['actionTaken'] ?? null,
            'status_id' => $statusId,
            'category_id' => $validated['categoryId'] ?? null,
        ];
        if ($isCompleted) {
            $updatePayload['archived'] = true;
        }
        $ticketRequest->update($updatePayload);

        if (isset($validated['systemDevelopmentSurvey']) && is_array($validated['systemDevelopmentSurvey'])) {
            $this->updateSystemDevelopmentSurvey($ticketRequest, $validated['systemDevelopmentSurvey']);
        }
        if (isset($validated['systemChangeRequestForm']) && is_array($validated['systemChangeRequestForm'])) {
            $this->updateSystemChangeRequestForm($ticketRequest, $validated['systemChangeRequestForm']);
        }
        if (isset($validated['systemIssueReport']) && is_array($validated['systemIssueReport'])) {
            $this->updateSystemIssueReport($ticketRequest, $validated['systemIssueReport'], $request, $auditLogger);
        }

        return redirect()->route('requests.it-governance.show', $ticketRequest)
            ->with('success', 'IT Governance request updated successfully.');
    }

    public function equipmentAndNetwork(Request $request, TicketRequest $ticketRequest)
    {
        $user = $request->user();
        if (! $user) {
            abort(401, 'Authentication required.');
        }
        if (! $user->isAdmin() && $ticketRequest->user_id !== $user->id && $ticketRequest->requested_for_user_id !== $user->id) {
            abort(403, 'You do not have permission to view this request.');
        }

        $canEdit = $user->isAdmin();

        $ticketRequest->load(['natureOfRequest', 'officeDesignation', 'status', 'category', 'remarks', 'assignedStaff', 'requestedForUser', 'user']);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        $requestedByUser = $ticketRequest->requestedForUser ?? $ticketRequest->user;
        [$fileAttachments, $systemDevelopmentSurvey, $systemChangeRequestForm, $systemIssueReport, $issueReportAttachments] = $this->splitAttachments(is_array($ticketRequest->attachments) ? $ticketRequest->attachments : []);

        $ticket = [
            'controlTicketNumber' => $ticketRequest->control_ticket_number,
            'requestedBy' => $requestedByUser?->name,
            'positionTitle' => $requestedByUser?->position_title,
            'office' => $ticketRequest->officeDesignation?->name,
            'dateFiled' => $ticketRequest->created_at?->toDateString(),
            'email' => $requestedByUser?->email,
            'natureOfRequest' => $ticketRequest->natureOfRequest?->name,
            'natureOfRequestId' => $ticketRequest->nature_of_request_id != null ? (string) $ticketRequest->nature_of_request_id : null,
            'requestDescription' => $ticketRequest->description,
            'attachments' => collect($fileAttachments)
                ->map(fn (array $attachment) => [
                    'name' => $attachment['name'] ?? basename($attachment['path'] ?? ''),
                    'url' => isset($attachment['path']) ? $disk->url($attachment['path']) : null,
                ])
                ->values()
                ->all(),
            'systemDevelopmentSurvey' => $systemDevelopmentSurvey,
            'systemChangeRequestForm' => $systemChangeRequestForm,
            'systemIssueReport' => $systemIssueReport,
            'systemIssueReportAttachments' => collect($issueReportAttachments)
                ->map(fn (array $a) => [
                    'name' => $a['name'] ?? basename($a['path'] ?? ''),
                    'url' => isset($a['path']) ? $disk->url($a['path']) : null,
                ])
                ->values()
                ->all(),
            'remarksId' => $ticketRequest->remarks_id != null ? (string) $ticketRequest->remarks_id : null,
            'assignedStaffId' => $ticketRequest->assigned_staff_id != null ? (string) $ticketRequest->assigned_staff_id : null,
            'dateReceived' => $ticketRequest->date_received?->toDateString(),
            'dateStarted' => $ticketRequest->date_started?->toDateString(),
            'timeStarted' => $ticketRequest->time_started?->toIso8601String(),
            'estimatedCompletionDate' => $ticketRequest->estimated_completion_date?->toDateString(),
            'timeCompleted' => $ticketRequest->time_completed?->toIso8601String(),
            'actionTaken' => $ticketRequest->action_taken,
            'categoryId' => $ticketRequest->category_id,
            'statusId' => $ticketRequest->status_id,
            'equipmentNetworkDetails' => $ticketRequest->equipment_network_details ?? [],
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

        $natureOfRequests = NatureOfRequest::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (NatureOfRequest $n) => ['id' => $n->id, 'name' => $n->name])
            ->values()
            ->all();

        return Inertia::render('requests/EquipmentAndNetwork', [
            'ticket' => $ticket,
            'updateUrl' => route('requests.equipment-network.update', $ticketRequest),
            'natureOfRequests' => $natureOfRequests,
            'staffOptions' => $staffOptions,
            'remarksOptions' => $remarksOptions,
            'categoryOptions' => $categoryOptions,
            'statusOptions' => $statusOptions,
            'canEdit' => $canEdit,
        ]);
    }

    public function updateEquipmentAndNetwork(Request $request, TicketRequest $ticketRequest)
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Only Admin and Super Admin can save equipment and network updates.');
        }

        $validated = $request->validate([
            'natureOfRequestId' => ['nullable', 'integer', 'exists:nature_of_requests,id'],
            'remarksId' => ['nullable', 'string'],
            'assignedStaffId' => ['nullable', 'string'],
            'dateReceived' => ['nullable', 'date'],
            'dateStarted' => ['nullable', 'date'],
            'estimatedCompletionDate' => ['nullable', 'date'],
            'actionTaken' => ['nullable', 'string', 'max:500'],
            'categoryId' => ['nullable', 'integer'],
            'statusId' => [
                'nullable',
                'integer',
                Rule::exists('reference_values', 'id')->where('group_key', ReferenceValueGroup::Status->value),
            ],
            'equipmentNetworkDetails' => ['nullable', 'array'],
            'equipmentNetworkDetails.rj45' => ['nullable', 'string', 'max:255'],
            'equipmentNetworkDetails.fiberOpticHeatShrink' => ['nullable', 'string', 'max:255'],
            'equipmentNetworkDetails.fiberOpticSClamp' => ['nullable', 'string', 'max:255'],
            'equipmentNetworkDetails.scConnector' => ['nullable', 'string', 'max:255'],
            'equipmentNetworkDetails.napBox' => ['nullable', 'string', 'max:255'],
            'equipmentNetworkDetails.fiberOpticMeters' => ['nullable', 'string', 'max:255'],
            'equipmentNetworkDetails.fiberOpticType' => ['nullable', 'string', 'max:255'],
            'equipmentNetworkDetails.utpCableMeters' => ['nullable', 'string', 'max:255'],
            'equipmentNetworkDetails.utpCableType' => ['nullable', 'string', 'max:255'],
            'equipmentNetworkDetails.sfpModuleQty' => ['nullable', 'string', 'max:255'],
            'equipmentNetworkDetails.sfpModuleBrand' => ['nullable', 'string', 'max:255'],
            'equipmentNetworkDetails.sfpModuleType' => ['nullable', 'string', 'max:255'],
            'equipmentNetworkDetails.sfpModuleSerial' => ['nullable', 'string', 'max:255'],
            'equipmentNetworkDetails.wifiRouterQty' => ['nullable', 'string', 'max:255'],
            'equipmentNetworkDetails.wifiRouterBrand' => ['nullable', 'string', 'max:255'],
            'equipmentNetworkDetails.wifiRouterSerial' => ['nullable', 'string', 'max:255'],
            'equipmentNetworkDetails.wifiRouterModel' => ['nullable', 'string', 'max:255'],
            'equipmentNetworkDetails.networkSwitchQty' => ['nullable', 'string', 'max:255'],
            'equipmentNetworkDetails.networkSwitchBrand' => ['nullable', 'string', 'max:255'],
            'equipmentNetworkDetails.networkSwitchSerial' => ['nullable', 'string', 'max:255'],
            'equipmentNetworkDetails.networkSwitchModel' => ['nullable', 'string', 'max:255'],
            'equipmentNetworkDetails.apBeamQty' => ['nullable', 'string', 'max:255'],
            'equipmentNetworkDetails.apBeamBrand' => ['nullable', 'string', 'max:255'],
            'equipmentNetworkDetails.apBeamSerial' => ['nullable', 'string', 'max:255'],
            'equipmentNetworkDetails.apBeamModel' => ['nullable', 'string', 'max:255'],
        ]);

        $this->validateRequestDateOrder($validated, 'dateReceived', 'dateStarted', 'estimatedCompletionDate');

        $remarksId = isset($validated['remarksId']) && $validated['remarksId'] !== '' ? (int) $validated['remarksId'] : null;
        $assignedStaffId = isset($validated['assignedStaffId']) && $validated['assignedStaffId'] !== '' ? (int) $validated['assignedStaffId'] : null;
        $natureOfRequestId = array_key_exists('natureOfRequestId', $validated)
            ? (isset($validated['natureOfRequestId']) && $validated['natureOfRequestId'] !== '' ? (int) $validated['natureOfRequestId'] : null)
            : $ticketRequest->nature_of_request_id;

        $dateStarted = $validated['dateStarted'] ?? null;
        $timeStarted = $ticketRequest->time_started;
        if ($dateStarted && ! $ticketRequest->time_started) {
            $timeStarted = now();
        }

        $resolvedStatusId = array_key_exists('statusId', $validated) && $validated['statusId'] !== '' && $validated['statusId'] !== null
            ? (int) $validated['statusId']
            : $ticketRequest->status_id;
        $timeCompleted = $ticketRequest->time_completed;
        if ($resolvedStatusId && ! $ticketRequest->time_completed) {
            $status = ReferenceValue::query()
                ->forGroup(ReferenceValueGroup::Status)
                ->where('id', $resolvedStatusId)
                ->first();
            if ($status?->name === 'Completed') {
                $timeCompleted = now();
            }
        }

        $updatePayload = [
            'nature_of_request_id' => $natureOfRequestId,
            'remarks_id' => $remarksId,
            'assigned_staff_id' => $assignedStaffId,
            'date_received' => $validated['dateReceived'] ?? null,
            'date_started' => $dateStarted,
            'time_started' => $timeStarted,
            'estimated_completion_date' => $validated['estimatedCompletionDate'] ?? null,
            'time_completed' => $timeCompleted,
            'action_taken' => $validated['actionTaken'] ?? null,
            'status_id' => $resolvedStatusId,
            'category_id' => $validated['categoryId'] ?? null,
            'equipment_network_details' => $this->normalizeEquipmentNetworkDetails($validated['equipmentNetworkDetails'] ?? []),
        ];
        $isCompletedStatus = $resolvedStatusId && ReferenceValue::query()
            ->forGroup(ReferenceValueGroup::Status)
            ->where('id', $resolvedStatusId)
            ->where('name', 'Completed')
            ->exists();
        if ($isCompletedStatus) {
            $updatePayload['archived'] = true;
        }
        $ticketRequest->update($updatePayload);

        $this->syncTicketRequestToEnrollment($ticketRequest);

        $ticketRequest->load(['natureOfRequest', 'status']);
        $isBorrowCompleted = $ticketRequest->natureOfRequest?->name === 'Borrow Unit'
            && $ticketRequest->status?->name === 'Completed';

        if ($isBorrowCompleted) {
            return redirect()->route('inventory', ['status' => 'borrowed'])
                ->with('success', 'Borrow marked as completed. The item has been removed from the active borrowed list.');
        }

        return redirect()->route('requests.equipment-network.show', $ticketRequest)
            ->with('success', 'Equipment and network request updated successfully.');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function validateRequestDateOrder(array $validated, string $receivedKey, string $startedKey, string $completionKey): void
    {
        $received = $validated[$receivedKey] ?? null;
        $started = $validated[$startedKey] ?? null;
        $completion = $validated[$completionKey] ?? null;

        if ($received && $started && $received > $started) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $startedKey => ['Date started must be on or after date received.'],
            ]);
        }
        if ($started && $completion && $started > $completion) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $completionKey => ['Estimated completion must be on or after date started.'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    private function normalizeEquipmentNetworkDetails(array $details): array
    {
        $keys = [
            'rj45', 'fiberOpticHeatShrink', 'fiberOpticSClamp', 'scConnector', 'napBox',
            'fiberOpticMeters', 'fiberOpticType', 'utpCableMeters', 'utpCableType',
            'sfpModuleQty', 'sfpModuleBrand', 'sfpModuleType', 'sfpModuleSerial',
            'wifiRouterQty', 'wifiRouterBrand', 'wifiRouterSerial', 'wifiRouterModel',
            'networkSwitchQty', 'networkSwitchBrand', 'networkSwitchSerial', 'networkSwitchModel',
            'apBeamQty', 'apBeamBrand', 'apBeamSerial', 'apBeamModel',
        ];
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = isset($details[$key]) && is_string($details[$key]) ? $details[$key] : '';
        }

        return $out;
    }

    private function syncTicketRequestToEnrollment(TicketRequest $ticketRequest): void
    {
        if (! $ticketRequest->qr_code_number) {
            return;
        }

        $enrollment = \App\Models\TicketEnrollment::where('unique_id', $ticketRequest->qr_code_number)->first();
        if (! $enrollment) {
            return;
        }

        $remarksName = $ticketRequest->remarks?->name;
        $assignedStaffName = $ticketRequest->assignedStaff?->name;

        $enrollment->update([
            'request_nature' => $ticketRequest->natureOfRequest?->name,
            'request_date' => $ticketRequest->date_received ?? $ticketRequest->created_at?->toDateString(),
            'request_action_taken' => $ticketRequest->action_taken,
            'request_assigned_staff' => $assignedStaffName,
            'request_remarks' => $remarksName,
        ]);
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

    /**
     * @param  array<int, mixed>|null  $attachments
     * @return array{0: array<int, array<string, mixed>>, 1: array<string, mixed>|null, 2: array<string, mixed>|null, 3: array<string, mixed>|null, 4: array<int, array<string, mixed>>}
     */
    /**
     * @param  array<int, mixed>|null  $attachments
     * @return array{0: array, 1: array|null, 2: array|null, 3: array|null, 4: array}
     */
    private function splitAttachments(?array $attachments): array
    {
        if (! is_array($attachments) || $attachments === []) {
            return [[], null, null, null, []];
        }

        $files = [];
        $survey = null;
        $systemChangeRequestForm = null;
        $systemIssueReport = null;
        $issueReportAttachments = [];

        foreach ($attachments as $attachment) {
            if (! is_array($attachment)) {
                continue;
            }

            if (($attachment['type'] ?? null) === 'system_development_survey') {
                $payload = $attachment['payload'] ?? null;
                if (is_array($payload)) {
                    $survey = $payload;
                }

                continue;
            }

            if (($attachment['type'] ?? null) === 'system_change_request_form') {
                $payload = $attachment['payload'] ?? null;
                if (is_array($payload)) {
                    $systemChangeRequestForm = $payload;
                }

                continue;
            }

            if (($attachment['type'] ?? null) === 'system_issue_report') {
                $payload = $attachment['payload'] ?? null;
                if (is_array($payload)) {
                    $systemIssueReport = $payload;
                }

                continue;
            }

            if (($attachment['type'] ?? null) === 'system_issue_report_attachment') {
                $issueReportAttachments[] = $attachment;

                continue;
            }

            if (isset($attachment['path'])) {
                $files[] = $attachment;
            }
        }

        return [$files, $survey, $systemChangeRequestForm, $systemIssueReport, $issueReportAttachments];
    }

    /**
     * Update (or create) the System Development survey payload inside attachments JSON.
     *
     * @param  array<string, mixed>  $updates
     */
    private function updateSystemDevelopmentSurvey(TicketRequest $ticketRequest, array $updates): void
    {
        $attachments = $ticketRequest->attachments ?? [];

        $surveyIndex = null;
        $surveyPayload = null;

        foreach ($attachments as $i => $attachment) {
            if (! is_array($attachment)) {
                continue;
            }
            if (($attachment['type'] ?? null) !== 'system_development_survey') {
                continue;
            }

            $surveyIndex = $i;
            $payload = $attachment['payload'] ?? null;
            $surveyPayload = is_array($payload) ? $payload : [];
            break;
        }

        if ($surveyPayload === null) {
            return;
        }

        foreach (['targetCompletion', 'assignedSystemsEngineer'] as $key) {
            if (array_key_exists($key, $updates)) {
                $surveyPayload[$key] = $updates[$key];
            }
        }

        $attachments[$surveyIndex] = [
            'type' => 'system_development_survey',
            'payload' => $surveyPayload,
        ];

        $ticketRequest->update([
            'attachments' => $attachments,
        ]);
    }

    /**
     * Update (or create) the System Change Request Form payload inside attachments JSON.
     * Only admin/staff fields should be updated during processing.
     *
     * @param  array<string, mixed>  $updates
     */
    private function updateSystemChangeRequestForm(TicketRequest $ticketRequest, array $updates): void
    {
        $attachments = $ticketRequest->attachments ?? [];

        $formIndex = null;
        $formPayload = null;

        foreach ($attachments as $i => $attachment) {
            if (! is_array($attachment)) {
                continue;
            }
            if (($attachment['type'] ?? null) !== 'system_change_request_form') {
                continue;
            }

            $formIndex = $i;
            $payload = $attachment['payload'] ?? null;
            $formPayload = is_array($payload) ? $payload : [];
            break;
        }

        if ($formPayload === null) {
            return;
        }

        foreach (['evaluatedBy', 'approvedBy', 'notedBy', 'remarks'] as $key) {
            if (array_key_exists($key, $updates)) {
                $formPayload[$key] = $updates[$key];
            }
        }

        $attachments[$formIndex] = [
            'type' => 'system_change_request_form',
            'payload' => $formPayload,
        ];

        $ticketRequest->update([
            'attachments' => $attachments,
        ]);
    }

    /**
     * Update System Issue Report staff/approval fields in attachments.
     *
     * @param  array<string, mixed>  $updates
     */
    private function updateSystemIssueReport(
        TicketRequest $ticketRequest,
        array $updates,
        Request $request,
        AuditLogger $auditLogger,
    ): void {
        $attachments = $ticketRequest->attachments ?? [];

        $formIndex = null;
        $formPayload = null;

        foreach ($attachments as $i => $attachment) {
            if (! is_array($attachment)) {
                continue;
            }
            if (($attachment['type'] ?? null) !== 'system_issue_report') {
                continue;
            }

            $formIndex = $i;
            $payload = $attachment['payload'] ?? null;
            $formPayload = is_array($payload) ? $payload : [];
            break;
        }

        if ($formPayload === null) {
            return;
        }

        $staffKeys = [
            'reportedBy', 'reportedByDate', 'reportedBySignature',
            'acceptedBy', 'acceptedByDate', 'acceptedBySignature',
            'evaluatedBy', 'evaluatedByDate', 'evaluatedBySignature',
            'approvedBy', 'approvedByDate', 'approvedBySignature',
        ];
        foreach ($staffKeys as $key) {
            if (array_key_exists($key, $updates)) {
                $formPayload[$key] = $updates[$key];
            }
        }

        $attachments[$formIndex] = [
            'type' => 'system_issue_report',
            'payload' => $formPayload,
        ];

        $ticketRequest->update([
            'attachments' => $attachments,
        ]);

        $auditLogger->log($request, 'ticket_request.system_issue_report.staff_updated', $ticketRequest, [
            'control_ticket_number' => $ticketRequest->control_ticket_number,
        ]);
    }
}

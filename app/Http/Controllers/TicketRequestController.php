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

class TicketRequestController extends Controller
{
    public function create()
    {
        $user = request()->user();
        $isAdmin = $user?->isAdmin() ?? false;
        $user?->load('officeDesignation');

        return Inertia::render('SubmitRequest', [
            'controlTicketNumber' => $this->generateControlTicketNumber(),
            'natureOfRequests' => NatureOfRequest::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
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

        return Inertia::render('TicketRequestConfirmation', [
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

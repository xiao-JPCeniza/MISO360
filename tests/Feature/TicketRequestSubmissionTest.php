<?php

namespace Tests\Feature;

use App\Enums\ReferenceValueGroup;
use App\Models\IssuedUid;
use App\Models\NatureOfRequest;
use App\Models\ReferenceValue;
use App\Models\TicketEnrollment;
use App\Models\TicketRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketRequestSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_ticket_request_with_attachments_and_qr_code(): void
    {
        Storage::fake('public');
        /** @var User $user */
        $user = User::factory()->create();
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'System Development',
            'is_active' => true,
        ]);
        $csrfToken = 'test-token';
        $controlTicketNumber = sprintf('CTN-%s-0001', now()->format('Ymd'));

        $response = $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->post('/submit-request', [
                '_token' => $csrfToken,
                'controlTicketNumber' => $controlTicketNumber,
                'natureOfRequestId' => $natureOfRequest->id,
                'description' => 'Please help with a system development request.',
                'hasQrCode' => true,
                'qrCodeNumber' => 'MIS-UID-00001',
                'systemDevelopmentSurvey' => [
                    'titleOfProposedSystem' => 'Sample Proposed System',
                    'servicesOrFeatures' => [
                        [
                            'serviceFeature' => 'User Management',
                            'specifics' => 'Add roles and permissions.',
                            'accessibility' => 'Admin/User View Only',
                        ],
                    ],
                    'dataGathering' => [
                        [
                            'dataRequired' => 'Employee records',
                            'specifics' => 'Name, ID number, office.',
                        ],
                    ],
                    'forms' => [
                        [
                            'titleOfForm' => 'User Registration Form',
                            'description' => 'Reference form for user onboarding.',
                        ],
                    ],
                    'flowSop' => 'Requester submits -> IT reviews -> Approval -> Development',
                    'headOfOffice' => 'Head of Office Name',
                ],
                'attachments' => [
                    UploadedFile::fake()->image('photo.jpg')->size(512),
                    UploadedFile::fake()->create('video.mp4', 1024, 'video/mp4'),
                ],
            ]);

        $response->assertRedirect();

        $ticketRequest = TicketRequest::firstOrFail();

        $this->assertDatabaseHas('ticket_requests', [
            'id' => $ticketRequest->id,
            'control_ticket_number' => $controlTicketNumber,
            'nature_of_request_id' => $natureOfRequest->id,
            'user_id' => $user->id,
        ]);
        $this->assertFalse($ticketRequest->has_qr_code);
        $this->assertNull($ticketRequest->qr_code_number);

        $this->assertNotEmpty($ticketRequest->attachments);
        $fileAttachment = collect($ticketRequest->attachments)
            ->first(fn (array $attachment) => isset($attachment['path']));
        $this->assertNotNull($fileAttachment);
        $this->assertTrue(Storage::disk('public')->exists($fileAttachment['path']));
    }

    public function test_system_development_requires_survey_form(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'System Development',
            'is_active' => true,
        ]);
        $csrfToken = 'test-token';
        $controlTicketNumber = sprintf('CTN-%s-0011', now()->format('Ymd'));

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->post('/submit-request', [
                '_token' => $csrfToken,
                'controlTicketNumber' => $controlTicketNumber,
                'natureOfRequestId' => $natureOfRequest->id,
                'description' => 'Please help with a system development request.',
                'hasQrCode' => false,
            ])
            ->assertSessionHasErrors([
                'systemDevelopmentSurvey',
            ]);
    }

    public function test_system_modification_requires_system_change_request_form(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'System modification',
            'is_active' => true,
        ]);
        $csrfToken = 'test-token';
        $controlTicketNumber = sprintf('CTN-%s-0022', now()->format('Ymd'));

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->post('/submit-request', [
                '_token' => $csrfToken,
                'controlTicketNumber' => $controlTicketNumber,
                'natureOfRequestId' => $natureOfRequest->id,
                'description' => 'Request for a system modification.',
                'hasQrCode' => false,
            ])
            ->assertSessionHasErrors([
                'systemChangeRequestForm',
            ]);
    }

    public function test_admin_can_submit_ticket_request_for_office_user(): void
    {
        $admin = User::factory()->admin()->create();
        $office = ReferenceValue::create([
            'group_key' => ReferenceValueGroup::OfficeDesignation->value,
            'name' => 'ICT Office',
            'system_seeded' => true,
            'is_active' => true,
        ]);
        $officeUser = User::factory()->create([
            'office_designation_id' => $office->id,
        ]);
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'Computer Repair',
            'is_active' => true,
        ]);
        $csrfToken = 'test-token';
        $controlTicketNumber = sprintf('CTN-%s-0004', now()->format('Ymd'));

        $this->actingAs($admin)
            ->withSession(['_token' => $csrfToken])
            ->post('/submit-request', [
                '_token' => $csrfToken,
                'controlTicketNumber' => $controlTicketNumber,
                'natureOfRequestId' => $natureOfRequest->id,
                'officeDesignationId' => $office->id,
                'requestedForUserId' => $officeUser->id,
                'description' => 'Device is not powering on.',
                'hasQrCode' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ticket_requests', [
            'control_ticket_number' => $controlTicketNumber,
            'office_designation_id' => $office->id,
            'requested_for_user_id' => $officeUser->id,
        ]);
    }

    public function test_ticket_request_requires_qr_code_number_when_enabled(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'Password reset',
            'is_active' => true,
        ]);
        $csrfToken = 'test-token';
        $controlTicketNumber = sprintf('CTN-%s-0002', now()->format('Ymd'));

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->post('/submit-request', [
                '_token' => $csrfToken,
                'controlTicketNumber' => $controlTicketNumber,
                'natureOfRequestId' => $natureOfRequest->id,
                'description' => 'Password reset needed for my account.',
                'hasQrCode' => true,
                'qrCodeNumber' => '',
            ])
            ->assertSessionHasErrors(['qrCodeNumber']);
    }

    public function test_admin_can_submit_ticket_with_unit_qr_code(): void
    {
        IssuedUid::create(['uid' => 'MIS-UID-00050', 'sequence' => 50]);
        $admin = User::factory()->admin()->create();
        $office = ReferenceValue::create([
            'group_key' => ReferenceValueGroup::OfficeDesignation->value,
            'name' => 'ICT Unit',
            'system_seeded' => true,
            'is_active' => true,
        ]);
        $officeUser = User::factory()->create(['office_designation_id' => $office->id]);
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'Computer repair',
            'is_active' => true,
        ]);
        $csrfToken = 'test-token';
        $controlTicketNumber = sprintf('CTN-%s-0050', now()->format('Ymd'));

        $response = $this->actingAs($admin)
            ->withSession(['_token' => $csrfToken])
            ->post('/submit-request', [
                '_token' => $csrfToken,
                'controlTicketNumber' => $controlTicketNumber,
                'natureOfRequestId' => $natureOfRequest->id,
                'officeDesignationId' => $office->id,
                'requestedForUserId' => $officeUser->id,
                'description' => 'Repair keyboard and assign unit QR.',
                'hasQrCode' => true,
                'qrCodeNumber' => 'MIS-UID-00050',
            ]);

        $response->assertRedirect();
        $ticketRequest = TicketRequest::firstOrFail();
        $this->assertTrue($ticketRequest->has_qr_code);
        $this->assertSame('MIS-UID-00050', $ticketRequest->qr_code_number);
    }

    public function test_ticket_request_requires_description_minimum_length(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'Computer Repair',
            'is_active' => true,
        ]);
        $csrfToken = 'test-token';
        $controlTicketNumber = sprintf('CTN-%s-0003', now()->format('Ymd'));

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->post('/submit-request', [
                '_token' => $csrfToken,
                'controlTicketNumber' => $controlTicketNumber,
                'natureOfRequestId' => $natureOfRequest->id,
                'description' => 'Too short',
                'hasQrCode' => false,
            ])
            ->assertSessionHasErrors(['description']);
    }

    public function test_submit_request_create_prefills_nature_when_service_param_matches(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'Computer repair',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->get('/submit-request?service='.urlencode('Computer repair'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('requests/SubmitRequest')
            ->has('preSelectedNatureId')
            ->where('preSelectedNatureId', $natureOfRequest->id)
        );
    }

    public function test_submit_request_create_resolves_service_case_insensitive(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'System Development',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->get('/submit-request?service='.urlencode('system development'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('preSelectedNatureId', $natureOfRequest->id)
        );
    }

    public function test_system_error_bug_report_requires_system_issue_report_form(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'System error / bug report',
            'is_active' => true,
        ]);
        $csrfToken = 'test-token';
        $controlTicketNumber = sprintf('CTN-%s-0055', now()->format('Ymd'));

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->post('/submit-request', [
                '_token' => $csrfToken,
                'controlTicketNumber' => $controlTicketNumber,
                'natureOfRequestId' => $natureOfRequest->id,
                'description' => 'The application crashes when submitting the form.',
                'hasQrCode' => false,
            ])
            ->assertSessionHasErrors([
                'systemIssueReport',
            ]);
    }

    public function test_user_can_submit_ticket_request_with_system_issue_report(): void
    {
        Storage::fake('public');
        /** @var User $user */
        $user = User::factory()->create();
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'System error / bug report',
            'is_active' => true,
        ]);
        $csrfToken = 'test-token';
        $controlTicketNumber = sprintf('CTN-%s-0066', now()->format('Ymd'));

        $response = $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->post('/submit-request', [
                '_token' => $csrfToken,
                'controlTicketNumber' => $controlTicketNumber,
                'natureOfRequestId' => $natureOfRequest->id,
                'description' => 'Data in the system does not match the correct data.',
                'hasQrCode' => false,
                'systemIssueReport' => [
                    'controlNumber' => $controlTicketNumber,
                    'requestingDepartment' => 'Mayors Office - RQPS',
                    'dateFiled' => now()->format('Y-m-d'),
                    'requestingEmployee' => $user->name,
                    'employeeContactNo' => '09171234567',
                    'employeeId' => 'EMP001',
                    'signatureOfEmployee' => $user->name,
                    'natureOfAppointment' => 'Permanent',
                    'nameOfSoftware' => 'RQueueSys',
                    'typeOfRequest' => ['Data Error', 'Display Issue'],
                    'errorSummaryTitle' => 'Data mismatch in reports',
                    'detailedDescription' => 'When generating the monthly report, the figures do not match the source data. Steps: 1) Login, 2) Open Reports, 3) Select Monthly. Error message: "Invalid aggregation".',
                ],
                'systemIssueReportAttachments' => [
                    UploadedFile::fake()->image('screenshot.png')->size(256),
                ],
            ]);

        $response->assertRedirect();

        $ticketRequest = TicketRequest::firstOrFail();
        $this->assertDatabaseHas('ticket_requests', [
            'id' => $ticketRequest->id,
            'control_ticket_number' => $controlTicketNumber,
            'nature_of_request_id' => $natureOfRequest->id,
            'user_id' => $user->id,
        ]);

        $this->assertNotEmpty($ticketRequest->attachments);
        $issueReportPayload = collect($ticketRequest->attachments)
            ->first(fn (array $a) => ($a['type'] ?? null) === 'system_issue_report');
        $this->assertNotNull($issueReportPayload);
        $payload = $issueReportPayload['payload'] ?? [];
        $this->assertSame('RQueueSys', $payload['nameOfSoftware'] ?? null);
        $this->assertSame('Data mismatch in reports', $payload['errorSummaryTitle'] ?? null);
        $this->assertContains('Data Error', $payload['typeOfRequest'] ?? []);

        $screenshotAttachment = collect($ticketRequest->attachments)
            ->first(fn (array $a) => ($a['type'] ?? null) === 'system_issue_report_attachment');
        $this->assertNotNull($screenshotAttachment);
        $this->assertTrue(Storage::disk('public')->exists($screenshotAttachment['path']));
    }

    public function test_admin_submit_with_uid_creates_enrollment(): void
    {
        IssuedUid::create(['uid' => 'MIS-UID-00099', 'sequence' => 99]);
        $admin = User::factory()->admin()->create();
        $office = ReferenceValue::create([
            'group_key' => ReferenceValueGroup::OfficeDesignation->value,
            'name' => 'ICT Unit',
            'system_seeded' => true,
            'is_active' => true,
        ]);
        $officeUser = User::factory()->create(['office_designation_id' => $office->id]);
        $natureOfRequest = NatureOfRequest::create(['name' => 'Computer repair', 'is_active' => true]);
        $csrfToken = 'test-token';
        $controlTicketNumber = sprintf('CTN-%s-9999', now()->format('Ymd'));

        $this->actingAs($admin)
            ->withSession(['_token' => $csrfToken])
            ->post('/submit-request', [
                '_token' => $csrfToken,
                'controlTicketNumber' => $controlTicketNumber,
                'natureOfRequestId' => $natureOfRequest->id,
                'officeDesignationId' => $office->id,
                'requestedForUserId' => $officeUser->id,
                'description' => 'New unit with QR code.',
                'hasQrCode' => true,
                'qrCodeNumber' => 'MIS-UID-00099',
            ])
            ->assertRedirect();

        $ticketRequest = TicketRequest::firstOrFail();
        $this->assertSame('MIS-UID-00099', $ticketRequest->qr_code_number);

        $enrollment = TicketEnrollment::where('unique_id', 'MIS-UID-00099')->first();
        $this->assertNotNull($enrollment);
    }
}

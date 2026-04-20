<?php

namespace Tests\Feature;

use App\Enums\ReferenceValueGroup;
use App\Models\IssuedUid;
use App\Models\NatureOfRequest;
use App\Models\ReferenceValue;
use App\Models\TicketEnrollment;
use App\Models\TicketRequest;
use App\Models\User;
use App\Notifications\Admin\NewTicketRequestSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketRequestSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_submission_creates_admin_notification(): void
    {
        $admin = User::factory()->admin()->create();

        /** @var User $user */
        $user = User::factory()->create();
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'Computer Repair',
            'is_active' => true,
        ]);
        $csrfToken = 'test-token';
        $controlTicketNumber = sprintf('CTN-%s-12345', now()->format('Y'));

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->post('/submit-request', [
                '_token' => $csrfToken,
                'controlTicketNumber' => $controlTicketNumber,
                'natureOfRequestId' => $natureOfRequest->id,
                'description' => 'Keyboard is broken.',
                'hasQrCode' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $admin->id,
            'type' => NewTicketRequestSubmittedNotification::class,
        ]);
    }

    public function test_user_can_submit_ticket_request_with_attachments_and_qr_code(): void
    {
        Storage::fake('public');
        IssuedUid::create(['uid' => 'MIS-UID-00001', 'sequence' => 1]);
        /** @var User $user */
        $user = User::factory()->create();
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'System Development',
            'is_active' => true,
        ]);
        $csrfToken = 'test-token';
        $controlTicketNumber = sprintf('CTN-%s-00001', now()->format('Y'));

        $response = $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->post('/submit-request', [
                '_token' => $csrfToken,
                'controlTicketNumber' => $controlTicketNumber,
                'natureOfRequestId' => $natureOfRequest->id,
                'description' => 'Please help with a system development request.',
                'hasQrCode' => true,
                'qrCodeNumber' => 'MIS-UID-00001',
                'systemDevelopmentSurveyFormAttachments' => [
                    0 => UploadedFile::fake()->create('systems-development-survey.pdf', 100, 'application/pdf'),
                ],
                'attachments' => [
                    UploadedFile::fake()->image('photo.jpg')->size(512),
                    UploadedFile::fake()->create('video.mp4', 1024, 'video/mp4'),
                    UploadedFile::fake()->create('request.pdf', 256, 'application/pdf'),
                    UploadedFile::fake()->create(
                        'request.docx',
                        256,
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    ),
                    UploadedFile::fake()->create(
                        'request.xlsx',
                        256,
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    ),
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
        $this->assertTrue($ticketRequest->has_qr_code);
        $this->assertSame('MIS-UID-00001', $ticketRequest->qr_code_number);

        $enrollment = TicketEnrollment::where('unique_id', 'MIS-UID-00001')->first();
        $this->assertNotNull($enrollment);
        $this->assertSame('System Development', $enrollment->request_nature);

        $this->assertNotEmpty($ticketRequest->attachments);
        $fileAttachment = collect($ticketRequest->attachments)
            ->first(fn (array $attachment) => isset($attachment['path']));
        $this->assertNotNull($fileAttachment);
        $this->assertTrue(Storage::disk('public')->exists($fileAttachment['path']));
    }

    public function test_user_can_submit_ticket_request_with_text_attachment(): void
    {
        Storage::fake('public');
        /** @var User $user */
        $user = User::factory()->create();
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'Computer Repair',
            'is_active' => true,
        ]);
        $csrfToken = 'test-token';
        $controlTicketNumber = sprintf('CTN-%s-00007', now()->format('Y'));

        $response = $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->post('/submit-request', [
                '_token' => $csrfToken,
                'controlTicketNumber' => $controlTicketNumber,
                'natureOfRequestId' => $natureOfRequest->id,
                'description' => 'Submitting a ticket with text attachment for diagnostics.',
                'hasQrCode' => false,
                'attachments' => [
                    UploadedFile::fake()->create('diagnostics.txt', 128, 'text/plain'),
                ],
            ]);

        $response->assertRedirect();

        $ticketRequest = TicketRequest::query()
            ->where('control_ticket_number', $controlTicketNumber)
            ->firstOrFail();
        $this->assertNotEmpty($ticketRequest->attachments);
        $attachment = collect($ticketRequest->attachments)
            ->first(fn (array $entry) => isset($entry['path']) && str_contains(($entry['name'] ?? ''), 'diagnostics.txt'));
        $this->assertNotNull($attachment);
        $this->assertTrue(Storage::disk('public')->exists($attachment['path']));
    }

    public function test_system_development_requires_uploaded_form(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'System Development',
            'is_active' => true,
        ]);
        $csrfToken = 'test-token';
        $controlTicketNumber = sprintf('CTN-%s-00011', now()->format('Y'));

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
                'systemDevelopmentSurveyFormAttachments',
            ]);
    }

    public function test_system_modification_requires_system_change_request_form_upload(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'System Modification',
            'is_active' => true,
        ]);
        $csrfToken = 'test-token';
        $controlTicketNumber = sprintf('CTN-%s-00022', now()->format('Y'));

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
                'systemChangeRequestFormAttachments',
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
        $controlTicketNumber = sprintf('CTN-%s-00004', now()->format('Y'));

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
        $controlTicketNumber = sprintf('CTN-%s-00002', now()->format('Y'));

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

    public function test_submit_request_rejects_unissued_qr_for_any_user(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'Computer repair',
            'is_active' => true,
        ]);
        $csrfToken = 'test-token';
        $controlTicketNumber = sprintf('CTN-%s-90001', now()->format('Y'));

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->post('/submit-request', [
                '_token' => $csrfToken,
                'controlTicketNumber' => $controlTicketNumber,
                'natureOfRequestId' => $natureOfRequest->id,
                'description' => 'Need repair for workstation with label.',
                'hasQrCode' => true,
                'qrCodeNumber' => 'MIS-UID-99999',
            ])
            ->assertSessionHasErrors(['qrCodeNumber']);

        $this->assertDatabaseMissing('ticket_requests', [
            'control_ticket_number' => $controlTicketNumber,
        ]);
    }

    public function test_password_reset_or_account_recovery_requires_personal_email(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'Password reset or account recovery (gov mail)',
            'is_active' => true,
        ]);
        $csrfToken = 'test-token';
        $controlTicketNumber = sprintf('CTN-%s-00101', now()->format('Y'));

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->post('/submit-request', [
                '_token' => $csrfToken,
                'controlTicketNumber' => $controlTicketNumber,
                'natureOfRequestId' => $natureOfRequest->id,
                'personalEmail' => '',
                'description' => 'Password reset needed for my account.',
                'hasQrCode' => false,
            ])
            ->assertSessionHasErrors(['personalEmail']);
    }

    public function test_password_reset_or_account_recovery_saves_personal_email(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'Password reset or account recovery (gov mail)',
            'is_active' => true,
        ]);
        $csrfToken = 'test-token';
        $controlTicketNumber = sprintf('CTN-%s-00102', now()->format('Y'));

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->post('/submit-request', [
                '_token' => $csrfToken,
                'controlTicketNumber' => $controlTicketNumber,
                'natureOfRequestId' => $natureOfRequest->id,
                'personalEmail' => 'personal@example.com',
                'description' => 'Password reset needed for my account.',
                'hasQrCode' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ticket_requests', [
            'control_ticket_number' => $controlTicketNumber,
            'personal_email' => 'personal@example.com',
        ]);
    }

    public function test_system_account_creation_requires_office_email(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'System account creation',
            'is_active' => true,
        ]);
        $csrfToken = 'test-token';
        $controlTicketNumber = sprintf('CTN-%s-00201', now()->format('Y'));

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->post('/submit-request', [
                '_token' => $csrfToken,
                'controlTicketNumber' => $controlTicketNumber,
                'natureOfRequestId' => $natureOfRequest->id,
                'officeEmail' => '',
                'description' => 'Request for new system account.',
                'hasQrCode' => false,
            ])
            ->assertSessionHasErrors(['officeEmail']);
    }

    public function test_system_account_creation_saves_office_email(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'System account creation',
            'is_active' => true,
        ]);
        $csrfToken = 'test-token';
        $controlTicketNumber = sprintf('CTN-%s-00202', now()->format('Y'));

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->post('/submit-request', [
                '_token' => $csrfToken,
                'controlTicketNumber' => $controlTicketNumber,
                'natureOfRequestId' => $natureOfRequest->id,
                'officeEmail' => 'newuser@agency.gov.ph',
                'description' => 'Request for new system account.',
                'hasQrCode' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ticket_requests', [
            'control_ticket_number' => $controlTicketNumber,
            'office_email' => 'newuser@agency.gov.ph',
        ]);
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
        $controlTicketNumber = sprintf('CTN-%s-00050', now()->format('Y'));

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

    public function test_submit_only_user_is_restricted_to_submit_request_route(): void
    {
        /** @var User $submitOnlyUser */
        $submitOnlyUser = User::factory()->create([
            'role' => 'submit_only',
            'email' => 'request@miso.gov.ph',
        ]);

        $this->actingAs($submitOnlyUser)
            ->get('/dashboard')
            ->assertRedirect('/submit-request');

        $this->actingAs($submitOnlyUser)
            ->get('/admin/dashboard')
            ->assertRedirect('/submit-request');

        $this->actingAs($submitOnlyUser)
            ->get('/requests')
            ->assertRedirect('/submit-request');

        $this->actingAs($submitOnlyUser)
            ->get('/submit-request')
            ->assertOk();
    }

    public function test_submit_only_user_can_submit_request_with_admin_like_fields(): void
    {
        IssuedUid::create(['uid' => 'MIS-UID-00123', 'sequence' => 123]);

        /** @var User $submitOnlyUser */
        $submitOnlyUser = User::factory()->create([
            'role' => 'submit_only',
            'email' => 'request@miso.gov.ph',
        ]);

        $office = ReferenceValue::create([
            'group_key' => ReferenceValueGroup::OfficeDesignation->value,
            'name' => 'MISO Request Unit',
            'system_seeded' => true,
            'is_active' => true,
        ]);
        $officeUser = User::factory()->create([
            'office_designation_id' => $office->id,
            'is_active' => true,
        ]);
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'Computer repair',
            'is_active' => true,
        ]);
        $controlTicketNumber = sprintf('CTN-%s-00123', now()->format('Y'));

        $csrfToken = 'test-token';
        $this->actingAs($submitOnlyUser)
            ->withSession(['_token' => $csrfToken])
            ->post('/submit-request', [
                '_token' => $csrfToken,
                'controlTicketNumber' => $controlTicketNumber,
                'natureOfRequestId' => $natureOfRequest->id,
                'officeDesignationId' => $office->id,
                'requestedForUserId' => $officeUser->id,
                'description' => 'Repair workstation and assign an issued UID.',
                'hasQrCode' => true,
                'qrCodeNumber' => 'MIS-UID-00123',
            ])
            ->assertRedirect('/submit-request');

        $this->assertDatabaseHas('ticket_requests', [
            'control_ticket_number' => $controlTicketNumber,
            'office_designation_id' => $office->id,
            'requested_for_user_id' => $officeUser->id,
            'has_qr_code' => true,
            'qr_code_number' => 'MIS-UID-00123',
        ]);
    }

    public function test_submit_only_user_can_submit_without_office_or_requested_for(): void
    {
        /** @var User $submitOnlyUser */
        $submitOnlyUser = User::factory()->create([
            'role' => 'submit_only',
            'office_designation_id' => null,
        ]);
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'Computer repair',
            'is_active' => true,
        ]);
        $controlTicketNumber = sprintf('CTN-%s-00999', now()->format('Y'));

        $csrfToken = 'test-token';
        $this->actingAs($submitOnlyUser)
            ->withSession(['_token' => $csrfToken])
            ->post('/submit-request', [
                '_token' => $csrfToken,
                'controlTicketNumber' => $controlTicketNumber,
                'natureOfRequestId' => $natureOfRequest->id,
                'description' => 'Submit only user submitting on own behalf.',
                'hasQrCode' => false,
            ])
            ->assertRedirect('/submit-request');

        $this->assertDatabaseHas('ticket_requests', [
            'control_ticket_number' => $controlTicketNumber,
            'office_designation_id' => null,
            'requested_for_user_id' => $submitOnlyUser->id,
        ]);
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
        $controlTicketNumber = sprintf('CTN-%s-00003', now()->format('Y'));

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

    public function test_standard_admin_requests_index_shows_only_unassigned_or_assigned_to_self(): void
    {
        foreach (['Pending', 'Ongoing'] as $name) {
            ReferenceValue::updateOrCreate(
                [
                    'group_key' => ReferenceValueGroup::Status->value,
                    'name' => $name,
                ],
                ['is_active' => true, 'system_seeded' => false],
            );
        }

        $pendingStatus = ReferenceValue::query()
            ->forGroup(ReferenceValueGroup::Status)
            ->where('name', 'Pending')
            ->firstOrFail();

        /** @var User $admin */
        $admin = User::factory()->admin()->create();
        /** @var User $otherAdmin */
        $otherAdmin = User::factory()->admin()->create(['name' => 'Other Admin']);
        /** @var User $regularUser */
        $regularUser = User::factory()->create();

        // Unassigned pending ticket – should be visible for admin
        $unassigned = TicketRequest::factory()->create([
            'assigned_staff_id' => null,
            'status_id' => $pendingStatus->id,
        ]);

        // Assigned to logged-in admin – should be visible for admin
        $assignedToSelf = TicketRequest::factory()->create([
            'assigned_staff_id' => $admin->id,
            'status_id' => $pendingStatus->id,
        ]);

        // Assigned to another admin – should NOT be visible for this admin
        TicketRequest::factory()->create([
            'assigned_staff_id' => $otherAdmin->id,
            'status_id' => $pendingStatus->id,
        ]);

        // Regular user request – should be visible only to that user, not filtered by assignment
        $userTicket = TicketRequest::factory()->create([
            'user_id' => $regularUser->id,
            'assigned_staff_id' => $admin->id,
            'status_id' => $pendingStatus->id,
        ]);

        $adminResponse = $this->actingAs($admin)->get('/requests');
        $adminResponse->assertOk();
        $adminResponse->assertInertia(fn ($page) => $page
            ->component('requests/Requests')
            ->has('requests', 3)
        );

        $userResponse = $this->actingAs($regularUser)->get('/requests');
        $userResponse->assertOk();
        $userResponse->assertInertia(fn ($page) => $page
            ->component('requests/Requests')
            ->has('requests', 1)
        );
    }

    public function test_system_error_bug_report_can_submit_without_embedded_system_issue_report_form(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'System error / bug report',
            'is_active' => true,
        ]);
        $csrfToken = 'test-token';
        $controlTicketNumber = sprintf('CTN-%s-00055', now()->format('Y'));

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->post('/submit-request', [
                '_token' => $csrfToken,
                'controlTicketNumber' => $controlTicketNumber,
                'natureOfRequestId' => $natureOfRequest->id,
                'description' => 'The application crashes when submitting the form.',
                'hasQrCode' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ticket_requests', [
            'control_ticket_number' => $controlTicketNumber,
            'nature_of_request_id' => $natureOfRequest->id,
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
        $controlTicketNumber = sprintf('CTN-%s-00066', now()->format('Y'));

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

        $pdfPath = 'ticket-requests/system-issue-reports/generated/system-issue-report-'.$ticketRequest->id.'.pdf';
        $this->assertTrue(Storage::disk('public')->exists($pdfPath));

        $downloadResponse = $this->actingAs($user)
            ->get(route('requests.system-issue-report.pdf', $ticketRequest));

        $downloadResponse->assertOk();
        $downloadResponse->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'System-Issue-Report-'.$controlTicketNumber.'.pdf',
            (string) $downloadResponse->headers->get('content-disposition', ''),
        );
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
        $controlTicketNumber = sprintf('CTN-%s-09999', now()->format('Y'));

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
        $this->assertSame('Computer repair', $enrollment->request_nature);
    }

    public function test_system_modification_submits_when_scr_pdf_uploaded_even_if_inline_form_incomplete(): void
    {
        Storage::fake('public');
        /** @var User $user */
        $user = User::factory()->create();
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'System Modification',
            'is_active' => true,
        ]);
        $csrfToken = 'test-token';
        $controlTicketNumber = sprintf('CTN-%s-00888', now()->format('Y'));

        $response = $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->post('/submit-request', [
                '_token' => $csrfToken,
                'controlTicketNumber' => $controlTicketNumber,
                'natureOfRequestId' => $natureOfRequest->id,
                'description' => 'Details are provided in the uploaded System Change Request Form (PDF).',
                'hasQrCode' => false,
                'systemChangeRequestFormAttachments' => [
                    0 => UploadedFile::fake()->create('completed-form.pdf', 200, 'application/pdf'),
                ],
                'systemChangeRequestForm' => [
                    'controlNumber' => $controlTicketNumber,
                    'date' => now()->format('Y-m-d'),
                    'officeDivision' => '',
                    'requestedByName' => '',
                    'nameOfSoftware' => '',
                    'typeOfRequest' => '',
                    'descriptionOfRequest' => '',
                    'purposeObjectiveOfModification' => '',
                    'detailedDescriptionOfRequestedChange' => '',
                    'evaluatedBy' => '',
                    'approvedBy' => '',
                    'notedBy' => '',
                    'remarks' => '',
                ],
            ]);

        $response->assertRedirect();

        $ticketRequest = TicketRequest::firstOrFail();
        $hasScrAttachment = collect($ticketRequest->attachments ?? [])
            ->contains(fn (array $a) => ($a['type'] ?? null) === 'system_change_request_form_attachment');
        $this->assertTrue($hasScrAttachment);
    }

    public function test_system_modification_accepts_scr_docx_upload(): void
    {
        Storage::fake('public');
        /** @var User $user */
        $user = User::factory()->create();
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'System Modification',
            'is_active' => true,
        ]);
        $csrfToken = 'test-token';
        $controlTicketNumber = sprintf('CTN-%s-00891', now()->format('Y'));

        $response = $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->post('/submit-request', [
                '_token' => $csrfToken,
                'controlTicketNumber' => $controlTicketNumber,
                'natureOfRequestId' => $natureOfRequest->id,
                'description' => 'Details are provided in the uploaded System Change Request Form.',
                'hasQrCode' => false,
                'systemChangeRequestFormAttachments' => [
                    0 => UploadedFile::fake()->create(
                        'completed-form.docx',
                        200,
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    ),
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ticket_requests', [
            'control_ticket_number' => $controlTicketNumber,
        ]);
    }

    public function test_system_modification_accepts_scr_pdf_with_application_octet_stream_mime(): void
    {
        Storage::fake('public');
        /** @var User $user */
        $user = User::factory()->create();
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'System Modification',
            'is_active' => true,
        ]);
        $csrfToken = 'test-token';
        $controlTicketNumber = sprintf('CTN-%s-00889', now()->format('Y'));

        $response = $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->post('/submit-request', [
                '_token' => $csrfToken,
                'controlTicketNumber' => $controlTicketNumber,
                'natureOfRequestId' => $natureOfRequest->id,
                'description' => 'Details are provided in the uploaded System Change Request Form (PDF).',
                'hasQrCode' => false,
                'systemChangeRequestFormAttachments' => [
                    0 => UploadedFile::fake()->create('saved-from-acrobat.pdf', 200, 'application/octet-stream'),
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ticket_requests', [
            'control_ticket_number' => $controlTicketNumber,
        ]);
    }

    public function test_request_for_new_system_module_or_enhancement_requires_scr_pdf(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'Request for new system module or enhancement',
            'is_active' => true,
        ]);
        $csrfToken = 'test-token';
        $controlTicketNumber = sprintf('CTN-%s-00890', now()->format('Y'));

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->post('/submit-request', [
                '_token' => $csrfToken,
                'controlTicketNumber' => $controlTicketNumber,
                'natureOfRequestId' => $natureOfRequest->id,
                'description' => 'Requesting a new module or enhancement for an existing system.',
                'hasQrCode' => false,
            ])
            ->assertSessionHasErrors([
                'systemChangeRequestFormAttachments',
            ]);
    }

    public function test_data_request_and_approval_requires_uploaded_form(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'Data request and approval',
            'is_active' => true,
        ]);
        $csrfToken = 'test-token';
        $controlTicketNumber = sprintf('CTN-%s-00892', now()->format('Y'));

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->post('/submit-request', [
                '_token' => $csrfToken,
                'controlTicketNumber' => $controlTicketNumber,
                'natureOfRequestId' => $natureOfRequest->id,
                'description' => 'Requesting data release and approval.',
                'hasQrCode' => false,
            ])
            ->assertSessionHasErrors([
                'dataReleaseRequestFormAttachments',
            ]);
    }

    public function test_data_request_and_approval_accepts_doc_upload(): void
    {
        Storage::fake('public');
        /** @var User $user */
        $user = User::factory()->create();
        $natureOfRequest = NatureOfRequest::create([
            'name' => 'Data request and approval',
            'is_active' => true,
        ]);
        $csrfToken = 'test-token';
        $controlTicketNumber = sprintf('CTN-%s-00893', now()->format('Y'));

        $response = $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->post('/submit-request', [
                '_token' => $csrfToken,
                'controlTicketNumber' => $controlTicketNumber,
                'natureOfRequestId' => $natureOfRequest->id,
                'description' => 'Details are provided in the uploaded Data Request and Approval Form.',
                'hasQrCode' => false,
                'dataReleaseRequestFormAttachments' => [
                    0 => UploadedFile::fake()->create('data-request.doc', 180, 'application/msword'),
                ],
            ]);

        $response->assertRedirect();

        $ticketRequest = TicketRequest::firstOrFail();
        $hasAttachment = collect($ticketRequest->attachments ?? [])
            ->contains(fn (array $a) => ($a['type'] ?? null) === 'data_release_request_form_attachment');
        $this->assertTrue($hasAttachment);
    }
}

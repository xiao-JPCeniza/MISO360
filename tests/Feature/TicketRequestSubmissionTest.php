<?php

namespace Tests\Feature;

use App\Enums\ReferenceValueGroup;
use App\Models\NatureOfRequest;
use App\Models\ReferenceValue;
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
            'has_qr_code' => true,
            'qr_code_number' => 'MIS-UID-00001',
        ]);

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
}

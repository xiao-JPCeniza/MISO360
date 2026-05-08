<?php

namespace Tests\Feature;

use App\Models\TicketRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ItProcessingOngoingModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_save_it_processing_ongoing_payload(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var TicketRequest $ticket */
        $ticket = TicketRequest::factory()->create([
            'user_id' => $user->id,
            'requested_for_user_id' => $user->id,
        ]);

        $csrfToken = 'test-token';

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->post(route('requests.it-processing.ongoing.save', $ticket), [
                '_token' => $csrfToken,
                'notes' => 'hello',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_save_notes_and_upload_and_delete_attachments(): void
    {
        Storage::fake('public');

        /** @var User $admin */
        $admin = User::factory()->admin()->create();
        /** @var TicketRequest $ticket */
        $ticket = TicketRequest::factory()->create();

        $csrfToken = 'test-token';

        $file = UploadedFile::fake()->create('doc.pdf', 120, 'application/pdf');

        $this->actingAs($admin)
            ->withSession([
                '_token' => $csrfToken,
                'two_factor.verified_at' => now()->timestamp,
            ])
            ->post(route('requests.it-processing.ongoing.save', $ticket), [
                '_token' => $csrfToken,
                'notes' => 'Initial notes',
                'attachments' => [$file],
            ])
            ->assertRedirect();

        $ticket->refresh();

        $attachments = is_array($ticket->attachments) ? $ticket->attachments : [];
        $this->assertNotEmpty($attachments);

        $payload = collect($attachments)->firstWhere('type', 'it_processing_ongoing');
        $this->assertIsArray($payload);
        $this->assertSame('Initial notes', $payload['payload']['notes'] ?? null);

        $fileAttachment = collect($attachments)->firstWhere('type', 'it_processing_ongoing_attachment');
        $this->assertIsArray($fileAttachment);
        $this->assertNotEmpty($fileAttachment['id'] ?? null);
        $this->assertNotEmpty($fileAttachment['path'] ?? null);

        Storage::disk('public')->assertExists((string) $fileAttachment['path']);

        $this->actingAs($admin)
            ->withSession([
                '_token' => $csrfToken,
                'two_factor.verified_at' => now()->timestamp,
            ])
            ->post(route('requests.it-processing.ongoing.save', $ticket), [
                '_token' => $csrfToken,
                'notes' => 'Updated notes',
                'deleteAttachmentIds' => [(string) $fileAttachment['id']],
            ])
            ->assertRedirect();

        $ticket->refresh();
        $attachments = is_array($ticket->attachments) ? $ticket->attachments : [];

        $this->assertNull(collect($attachments)->firstWhere('id', (string) $fileAttachment['id']));
        Storage::disk('public')->assertMissing((string) $fileAttachment['path']);
    }
}

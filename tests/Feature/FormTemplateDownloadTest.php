<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FormTemplateDownloadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private static function formSlugsAndDownloadNames(): array
    {
        return [
            ['systems-development-survey', 'Systems Development Survey Form.pdf'],
            ['access-rights-enrolment', 'Access Rights Enrolment Form.pdf'],
            ['system-issue-report', 'System Issue Report Form.pdf'],
            ['system-change-request', 'System Change Request Form.pdf'],
        ];
    }

    public function test_authenticated_user_can_download_each_bundled_form_when_public_disk_has_no_copy(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        foreach (self::formSlugsAndDownloadNames() as [$slug, $expectedFilename]) {
            $response = $this->actingAs($user)->get(route('forms.download', ['form' => $slug]));

            $response->assertOk();
            $response->assertHeader('content-type', 'application/pdf');
            $this->assertStringContainsString(
                $expectedFilename,
                (string) $response->headers->get('content-disposition', ''),
            );
        }
    }

    public function test_unknown_form_template_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('forms.download', ['form' => 'nonexistent-form']))
            ->assertNotFound();
    }

    public function test_guest_cannot_download_form_templates(): void
    {
        $this->get(route('forms.download', ['form' => 'systems-development-survey']))
            ->assertRedirect();
    }

    public function test_submit_request_page_includes_working_form_download_urls(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('submit-request'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('requests/SubmitRequest')
            ->where('formDownloadUrls.systemsDevelopmentSurvey', route('forms.download', ['form' => 'systems-development-survey']))
            ->where('formDownloadUrls.accessRightsEnrolment', route('forms.download', ['form' => 'access-rights-enrolment']))
            ->where('formDownloadUrls.systemIssueReport', route('forms.download', ['form' => 'system-issue-report']))
            ->where('formDownloadUrls.systemChangeRequest', route('forms.download', ['form' => 'system-change-request']))
        );
    }
}

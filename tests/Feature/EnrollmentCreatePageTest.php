<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EnrollmentCreatePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_enrollment_create_page(): void
    {
        $this->get(route('admin.enrollments.create'))
            ->assertRedirect('/login');
    }

    public function test_admin_can_access_enrollment_create_page_and_receives_prefilled_uid(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.enrollments.create', ['unique_id' => 'mis-uid-00063']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('enrollment/TicketEnrollment')
            ->where('mode', 'create')
            ->where('prefillId', 'MIS-UID-00063')
            ->has('natureOfRequests')
            ->has('referenceOptions')
            ->has('referenceOptions.status')
            ->has('referenceOptions.category')
            ->has('referenceOptions.officeDesignation')
            ->has('referenceOptions.remarks')
        );
    }
}

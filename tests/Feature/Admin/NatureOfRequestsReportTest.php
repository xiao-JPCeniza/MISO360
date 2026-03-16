<?php

namespace Tests\Feature\Admin;

use App\Models\NatureOfRequest;
use App\Models\TicketRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NatureOfRequestsReportTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsTwoFactorVerified(User $user): static
    {
        return $this->actingAs($user)->withSession([
            'two_factor.verified_at' => now()->timestamp,
        ]);
    }

    public function test_guest_cannot_access_nature_of_requests_report(): void
    {
        $this->get(route('admin.reports.nature-of-requests'))
            ->assertRedirect('/login');
    }

    public function test_admin_cannot_access_nature_of_requests_report(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAsTwoFactorVerified($admin)
            ->get(route('admin.reports.nature-of-requests'))
            ->assertForbidden();
    }

    public function test_super_admin_can_view_nature_of_requests_report_with_monthly_totals(): void
    {
        $superAdmin = User::factory()->superAdmin()->create([
            'name' => 'Report Preparer',
            'position_title' => 'MIS Officer',
        ]);

        $natureA = NatureOfRequest::create([
            'name' => 'Assess extent of hardware/software failure',
            'is_active' => true,
        ]);
        $natureB = NatureOfRequest::create([
            'name' => 'Installation of application software',
            'is_active' => true,
        ]);

        TicketRequest::factory()->create([
            'nature_of_request_id' => $natureA->id,
            'created_at' => '2026-07-05 10:00:00',
            'updated_at' => '2026-07-05 10:00:00',
        ]);
        TicketRequest::factory()->create([
            'nature_of_request_id' => $natureA->id,
            'created_at' => '2026-07-20 10:00:00',
            'updated_at' => '2026-07-20 10:00:00',
        ]);
        TicketRequest::factory()->create([
            'nature_of_request_id' => $natureA->id,
            'created_at' => '2026-08-02 10:00:00',
            'updated_at' => '2026-08-02 10:00:00',
        ]);

        TicketRequest::factory()->create([
            'nature_of_request_id' => $natureB->id,
            'created_at' => '2026-07-01 10:00:00',
            'updated_at' => '2026-07-01 10:00:00',
        ]);
        TicketRequest::factory()->create([
            'nature_of_request_id' => $natureB->id,
            'created_at' => '2026-12-03 10:00:00',
            'updated_at' => '2026-12-03 10:00:00',
        ]);
        TicketRequest::factory()->create([
            'nature_of_request_id' => $natureB->id,
            'created_at' => '2026-12-10 10:00:00',
            'updated_at' => '2026-12-10 10:00:00',
        ]);
        TicketRequest::factory()->create([
            'nature_of_request_id' => $natureB->id,
            'created_at' => '2026-12-29 10:00:00',
            'updated_at' => '2026-12-29 10:00:00',
        ]);

        $response = $this->actingAsTwoFactorVerified($superAdmin)
            ->get(route('admin.reports.nature-of-requests', ['year' => 2026]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/reports/NatureOfRequestsReport')
            ->where('year', 2026)
            ->has('months', 12)
            ->has('rows', 2)
            ->where('rows.0.nature', 'Assess extent of hardware/software failure')
            ->where('rows.0.counts.7', 2)
            ->where('rows.0.counts.8', 1)
            ->where('rows.0.total', 3)
            ->where('rows.1.nature', 'Installation of application software')
            ->where('rows.1.counts.7', 1)
            ->where('rows.1.counts.12', 3)
            ->where('rows.1.total', 4)
            ->where('monthTotals.7', 3)
            ->where('monthTotals.8', 1)
            ->where('monthTotals.12', 3)
            ->where('grandTotal', 7)
            ->where('preparedBy.name', 'Report Preparer')
            ->where('preparedBy.positionTitle', 'MIS Officer')
        );
    }
}

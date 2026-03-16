<?php

namespace Tests\Feature\Admin;

use App\Enums\ReferenceValueGroup;
use App\Models\NatureOfRequest;
use App\Models\ReferenceValue;
use App\Models\TicketRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsTwoFactorVerified(User $user): static
    {
        return $this->actingAs($user)->withSession([
            'two_factor.verified_at' => now()->timestamp,
        ]);
    }

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_can_access_dashboard_and_sees_stats_and_queue(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAsTwoFactorVerified($admin)
            ->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/AdminDashboard')
            ->has('stats')
            ->where('stats.activeInQueue', 0)
            ->where('stats.assignedToMe', 0)
            ->where('stats.totalReceived', 0)
            ->has('activeQueue')
            ->has('archive')
            ->has('filters')
            ->has('sort')
            ->has('staffOptions')
        );
    }

    public function test_guest_cannot_access_archive_export(): void
    {
        $this->get(route('admin.dashboard.archive-export'))
            ->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_archive_export(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard.archive-export'))
            ->assertForbidden();
    }

    public function test_admin_can_download_archive_export(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAsTwoFactorVerified($admin)
            ->get(route('admin.dashboard.archive-export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_archive_export_accepts_assigned_staff_id_filter(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->admin()->create(['name' => 'MIS Staff One']);

        $response = $this->actingAsTwoFactorVerified($admin)
            ->get(route('admin.dashboard.archive-export', ['assigned_staff_id' => $staff->id]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_guest_cannot_access_nature_monthly_summary_export(): void
    {
        $this->get(route('admin.dashboard.nature-monthly-summary-export'))
            ->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_nature_monthly_summary_export(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard.nature-monthly-summary-export'))
            ->assertForbidden();
    }

    public function test_admin_cannot_access_nature_monthly_summary_export(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAsTwoFactorVerified($admin)
            ->get(route('admin.dashboard.nature-monthly-summary-export'))
            ->assertForbidden();
    }

    public function test_super_admin_can_download_nature_monthly_summary_export_and_contains_counts(): void
    {
        $superAdmin = User::factory()->superAdmin()->create([
            'name' => 'Junel JG Jimenez',
            'position_title' => 'MGADH - I / MISO OIC',
        ]);

        $lan = NatureOfRequest::create(['name' => 'LAN Cabling', 'is_active' => true]);
        $softwareDev = NatureOfRequest::create(['name' => 'Software Development', 'is_active' => true]);

        TicketRequest::factory()->create([
            'nature_of_request_id' => $lan->id,
            'created_at' => CarbonImmutable::create(2026, 1, 5, 10, 0, 0),
        ]);
        TicketRequest::factory()->create([
            'nature_of_request_id' => $lan->id,
            'created_at' => CarbonImmutable::create(2026, 1, 20, 10, 0, 0),
        ]);
        TicketRequest::factory()->create([
            'nature_of_request_id' => $lan->id,
            'created_at' => CarbonImmutable::create(2026, 2, 1, 10, 0, 0),
        ]);
        TicketRequest::factory()->create([
            'nature_of_request_id' => $softwareDev->id,
            'created_at' => CarbonImmutable::create(2026, 2, 15, 10, 0, 0),
        ]);

        $response = $this->actingAsTwoFactorVerified($superAdmin)
            ->get(route('admin.dashboard.nature-monthly-summary-export', ['year' => 2026]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        /** @var \Symfony\Component\HttpFoundation\BinaryFileResponse $baseResponse */
        $baseResponse = $response->baseResponse;
        $file = $baseResponse->getFile();
        $spreadsheet = IOFactory::createReader('Xlsx')->load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();

        $lanRow = $this->findNatureRow($sheet, 'LAN Cabling');
        $softwareRow = $this->findNatureRow($sheet, 'Software Development');

        $this->assertSame(2, (int) $sheet->getCell("C{$lanRow}")->getValue()); // January
        $this->assertSame(1, (int) $sheet->getCell("D{$lanRow}")->getValue()); // February
        $this->assertSame(3, (int) $sheet->getCell("O{$lanRow}")->getValue()); // Total

        $this->assertSame(0, (int) $sheet->getCell("C{$softwareRow}")->getValue()); // January
        $this->assertSame(1, (int) $sheet->getCell("D{$softwareRow}")->getValue()); // February
        $this->assertSame(1, (int) $sheet->getCell("O{$softwareRow}")->getValue()); // Total

        $this->assertSame('JUNEL JG JIMENEZ', (string) $sheet->getCell('A10')->getValue());
        $this->assertSame('JUNEL JG JIMENEZ', (string) $sheet->getCell('A16')->getValue());
    }

    private function findNatureRow(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $natureName): int
    {
        for ($row = 1; $row <= 200; $row++) {
            $cell = trim((string) $sheet->getCell("B{$row}")->getValue());
            if ($cell === $natureName) {
                return $row;
            }
        }

        $this->fail("Nature row not found for: {$natureName}");
    }

    public function test_assigned_to_me_count_reflects_active_requests_assigned_to_logged_in_admin(): void
    {
        foreach (['Pending', 'Ongoing', 'Completed'] as $name) {
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
        $completedStatus = ReferenceValue::query()
            ->forGroup(ReferenceValueGroup::Status)
            ->where('name', 'Completed')
            ->firstOrFail();

        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create(['name' => 'Other Admin']);

        TicketRequest::factory()->create([
            'assigned_staff_id' => $admin->id,
            'status_id' => $pendingStatus->id,
        ]);
        TicketRequest::factory()->create([
            'assigned_staff_id' => $admin->id,
            'status_id' => $completedStatus->id,
        ]);
        TicketRequest::factory()->create([
            'assigned_staff_id' => $otherAdmin->id,
            'status_id' => $pendingStatus->id,
        ]);

        $response = $this->actingAsTwoFactorVerified($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/AdminDashboard')
            ->has('stats')
            ->where('stats.assignedToMe', 1)
        );
    }
}

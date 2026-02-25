<?php

namespace Tests\Feature;

use App\Models\QrBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class QrGeneratorBatchTest extends TestCase
{
    use RefreshDatabase;

    private function adminSession(): array
    {
        return [
            '_token' => 'test-token',
            'two_factor.verified_at' => Carbon::now()->timestamp,
        ];
    }

    public function test_guest_cannot_list_batches(): void
    {
        $response = $this->get('/admin/qr-generator/batches');

        $response->assertRedirect();
    }

    public function test_guest_cannot_show_batch(): void
    {
        $batch = QrBatch::create([
            'start_sequence' => 1,
            'end_sequence' => 20,
        ]);

        $response = $this->get("/admin/qr-generator/batches/{$batch->id}");

        $response->assertRedirect();
    }

    public function test_admin_can_list_batches_empty(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->withSession($this->adminSession())
            ->getJson('/admin/qr-generator/batches');

        $response->assertOk();
        $response->assertJsonPath('batches', []);
    }

    public function test_admin_can_list_batches_after_generating(): void
    {
        $admin = User::factory()->admin()->create();
        $batch = QrBatch::create([
            'start_sequence' => 1,
            'end_sequence' => 20,
        ]);

        $response = $this->actingAs($admin)
            ->withSession($this->adminSession())
            ->getJson('/admin/qr-generator/batches');

        $response->assertOk();
        $response->assertJsonCount(1, 'batches');
        $response->assertJsonPath('batches.0.id', $batch->id);
        $response->assertJsonPath('batches.0.start', 1);
        $response->assertJsonPath('batches.0.end', 20);
        $response->assertJsonPath('batches.0.count', 20);
    }

    public function test_admin_can_show_batch_and_get_ids(): void
    {
        $admin = User::factory()->admin()->create();
        $batch = QrBatch::create([
            'start_sequence' => 1,
            'end_sequence' => 3,
        ]);

        $response = $this->actingAs($admin)
            ->withSession($this->adminSession())
            ->getJson("/admin/qr-generator/batches/{$batch->id}");

        $response->assertOk();
        $response->assertJsonPath('ids', ['MIS-UID-00001', 'MIS-UID-00002', 'MIS-UID-00003']);
    }

    public function test_post_batch_creates_qr_batch_record(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->withSession($this->adminSession())
            ->postJson('/admin/qr-generator/batch', [
                'quantity' => 5,
                '_token' => 'test-token',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('qr_batches', 1);
        $batch = QrBatch::first();
        $this->assertSame(1, $batch->start_sequence);
        $this->assertSame(5, $batch->end_sequence);
    }
}

<?php

namespace Tests\Feature;

use App\Models\ProfileSlide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileSlidePostManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_access_post_management(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->withSession([
                '_token' => 'test-token',
                'two_factor.verified_at' => Carbon::now()->timestamp,
            ])
            ->get('/admin/posts');

        $response->assertStatus(403);
    }

    public function test_super_admin_can_access_post_management_index(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin)
            ->withSession([
                '_token' => 'test-token',
                'two_factor.verified_at' => Carbon::now()->timestamp,
            ])
            ->get('/admin/posts');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('admin/posts/Index')->has('slides'));
    }

    public function test_home_page_receives_profile_slides(): void
    {
        Storage::fake('public');
        ProfileSlide::factory()->count(2)->create([
            'is_active' => true,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('public/Welcome')
            ->has('profileSlides')
            ->where('profileSlides', fn ($slides) => count($slides) === 2));
    }
}

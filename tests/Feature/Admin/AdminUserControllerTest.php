<?php

namespace Tests\Feature\Admin;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class AdminUserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_forbidden_from_admin_pages(): void
    {
        $member = User::factory()->member()->create();
        $targetUser = User::factory()->member()->create();

        $this->actingAs($member)->get('/admin/users')->assertStatus(403);
        $this->actingAs($member)->get("/admin/users/{$targetUser->id}/posts")->assertStatus(403);
        $this->actingAs($member)->get("/admin/users/{$targetUser->id}/posts/export")->assertStatus(403);
    }

    public function test_admin_can_view_user_list(): void
    {
        $admin = User::factory()->admin()->create();
        $budi = User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@test.com']);
        User::factory()->create(['name' => 'Citra Lestari', 'email' => 'citra@test.com']);
        Post::factory()->count(2)->create(['user_id' => $budi->id]);

        $this->actingAs($admin)->get('/admin/users')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/UserList')
                ->has('users.data')
            );

        $this->actingAs($admin)->get('/admin/users?search=budi')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/UserList')
                ->has('users.data', 1)
                ->where('users.data.0.name', 'Budi Santoso')
                ->where('users.data.0.posts_count', 2)
            );

        $this->actingAs($admin)->get('/admin/users?search[]=x')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->where('filters.search', ''));
    }

    public function test_admin_can_view_user_posts_read_only(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->member()->create(['name' => 'Doni Target']);

        Post::factory()->published()->create(['user_id' => $target->id, 'title' => 'Artikel Spesifik Doni', 'category' => 'Teknologi']);
        Post::factory()->draft()->create(['user_id' => $target->id, 'title' => 'Artikel Lain Doni', 'category' => 'Opini']);
        Post::factory()->create(['title' => 'Bukan Milik Doni']);

        $this->actingAs($admin)->get("/admin/users/{$target->id}/posts")
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/UserPostList')
                ->where('targetUser.name', 'Doni Target')
                ->has('posts.data', 2)
            );

        $this->actingAs($admin)->get("/admin/users/{$target->id}/posts?search=Spesifik")
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Artikel Spesifik Doni')
            );

        $this->actingAs($admin)->get("/admin/users/{$target->id}/posts?status=draft")
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Artikel Lain Doni')
            );

        $this->actingAs($admin)->get("/admin/users/{$target->id}/posts?category=Teknologi")
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('filters.category', 'Teknologi')
            );

        $this->actingAs($admin)->get("/admin/users/{$target->id}/posts?search[]=a&status[]=b&category[]=c")
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 2)
                ->where('filters.search', '')
                ->where('filters.status', '')
                ->where('filters.category', '')
            );
    }

    public function test_admin_can_export_user_posts(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->member()->create(['name' => 'Budi Target']);
        Post::factory()->count(2)->create(['user_id' => $target->id]);

        $response = $this->actingAs($admin)->get("/admin/users/{$target->id}/posts/export");

        $this->assertInstanceOf(StreamedResponse::class, $response->baseResponse);
        $this->assertStringContainsString('Budi_Target', (string) $response->headers->get('content-disposition'));
    }
}

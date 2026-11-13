<?php

namespace Tests\Feature\Post;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class AdminPostControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_and_guest_cannot_access_admin_posts(): void
    {
        $this->get('/admin/posts')->assertRedirect(route('login'));

        $member = User::factory()->member()->create();
        $this->actingAs($member)->get('/admin/posts')->assertStatus(403);
        $this->actingAs($member)->get('/admin/posts/export')->assertStatus(403);
    }

    public function test_admin_monitors_all_posts_with_filters(): void
    {
        $admin = User::factory()->admin()->create();
        $author = User::factory()->member()->create(['name' => 'Penulis A']);
        Post::factory()->published()->create(['user_id' => $author->id, 'title' => 'Terbit Teknologi', 'category' => 'Teknologi']);
        Post::factory()->draft()->create(['title' => 'Draf Bisnis', 'category' => 'Bisnis']);

        $this->actingAs($admin)->get('/admin/posts')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/PostList')
                ->has('posts.data', 2)
                ->where('stats.total', 2)
                ->where('stats.published', 1)
                ->where('stats.draft', 1)
            );

        $this->actingAs($admin)->get('/admin/posts?search=Terbit')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.author.name', 'Penulis A')
            );

        $this->actingAs($admin)->get('/admin/posts?status=draft&category=Bisnis')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Draf Bisnis')
            );
    }

    public function test_admin_filters_ignore_non_string_values(): void
    {
        $admin = User::factory()->admin()->create();
        Post::factory()->create();

        $this->actingAs($admin)->get('/admin/posts?search[]=a&status[]=b&category[]=c')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('filters.search', '')
                ->where('filters.status', '')
                ->where('filters.category', '')
            );
    }

    public function test_admin_can_export_all_posts(): void
    {
        $admin = User::factory()->admin()->create();
        Post::factory()->count(2)->create();

        $response = $this->actingAs($admin)->get('/admin/posts/export');

        $this->assertInstanceOf(StreamedResponse::class, $response->baseResponse);
        $this->assertStringContainsString('spreadsheetml', (string) $response->headers->get('content-type'));
    }
}

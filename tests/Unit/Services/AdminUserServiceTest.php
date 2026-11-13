<?php

namespace Tests\Unit\Services;

use App\Features\Admin\Services\AdminUserService;
use App\Features\Post\Models\Post;
use App\Features\Post\Repositories\PostRepository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AdminUserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AdminUserService(new PostRepository());
    }

    public function test_get_users_list_without_search(): void
    {
        User::factory()->count(3)->create();

        $this->assertCount(3, $this->service->getUsersList());
    }

    public function test_get_users_list_with_search(): void
    {
        User::factory()->create(['name' => 'Alice Wonder', 'email' => 'alice@test.com']);
        User::factory()->create(['name' => 'Bob Marley', 'email' => 'bob@test.com']);
        User::factory()->create(['name' => 'Charlie Chaplin', 'username' => 'charlie_legend', 'email' => 'charlie@test.com']);

        $this->assertCount(1, $this->service->getUsersList(search: 'alice'));
        $this->assertCount(1, $this->service->getUsersList(search: 'bob@test.com'));
        $this->assertCount(1, $this->service->getUsersList(search: 'charlie_legend'));
    }

    public function test_get_users_list_counts_posts(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertSame(2, $this->service->getUsersList()->items()[0]->posts_count);
    }

    public function test_get_user_posts_and_all_user_posts(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(4)->create(['user_id' => $user->id]);
        Post::factory()->create();

        $this->assertCount(4, $this->service->getUserPosts($user->id));
        $this->assertCount(4, $this->service->getAllUserPosts($user->id));
    }
}

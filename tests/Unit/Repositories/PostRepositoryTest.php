<?php

namespace Tests\Unit\Repositories;

use App\Features\Post\Models\Post;
use App\Features\Post\Repositories\PostRepository;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected PostRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new PostRepository();
    }

    public function test_paginate_published_filters_and_orders(): void
    {
        Post::factory()->published()->create(['title' => 'Lama', 'category' => 'Umum', 'published_at' => now()->subDays(2)]);
        Post::factory()->published()->create(['title' => 'Baru Laravel', 'category' => 'Teknologi', 'published_at' => now()]);
        Post::factory()->draft()->create(['title' => 'Draf Laravel', 'category' => 'Teknologi']);

        $all = $this->repository->paginatePublished();
        $this->assertCount(2, $all);
        $this->assertSame('Baru Laravel', $all->items()[0]->title);

        $this->assertCount(1, $this->repository->paginatePublished('laravel'));
        $this->assertCount(1, $this->repository->paginatePublished(null, 'Umum'));
        $this->assertCount(0, $this->repository->paginatePublished('laravel', 'Umum'));
    }

    public function test_find_published_by_slug_or_fail(): void
    {
        $published = Post::factory()->published()->create(['slug' => 'artikel-terbit']);
        Post::factory()->draft()->create(['slug' => 'artikel-draf']);

        $this->assertSame($published->id, $this->repository->findPublishedBySlugOrFail('artikel-terbit')->id);

        $this->expectException(ModelNotFoundException::class);
        $this->repository->findPublishedBySlugOrFail('artikel-draf');
    }

    public function test_related_published(): void
    {
        $main = Post::factory()->published()->create(['category' => 'Opini']);
        Post::factory()->published()->count(4)->create(['category' => 'Opini']);
        Post::factory()->published()->create(['category' => 'Bisnis']);
        Post::factory()->draft()->create(['category' => 'Opini']);

        $related = $this->repository->relatedPublished($main, 3);

        $this->assertCount(3, $related);
        $this->assertFalse($related->contains('id', $main->id));
        $this->assertTrue($related->every(fn (Post $post) => $post->category === 'Opini' && $post->is_published));
    }

    public function test_for_user_applies_status_search_and_category(): void
    {
        $user = User::factory()->create();
        Post::factory()->published()->create(['user_id' => $user->id, 'title' => 'Satu', 'category' => 'Bisnis']);
        Post::factory()->draft()->create(['user_id' => $user->id, 'title' => 'Dua', 'category' => 'Opini']);
        Post::factory()->published()->create(['title' => 'Orang Lain']);

        $this->assertCount(2, $this->repository->forUser($user->id));
        $this->assertCount(1, $this->repository->forUser($user->id, 'published'));
        $this->assertCount(1, $this->repository->forUser($user->id, 'draft'));
        $this->assertCount(2, $this->repository->forUser($user->id, 'bogus'));
        $this->assertCount(1, $this->repository->forUser($user->id, null, 'dua'));
        $this->assertCount(1, $this->repository->forUser($user->id, null, null, 'Bisnis'));
    }

    public function test_paginate_all_includes_every_author(): void
    {
        Post::factory()->published()->create(['title' => 'Zebra Unik', 'category' => 'Umum']);
        Post::factory()->draft()->create(['title' => 'B', 'category' => 'Opini']);

        $this->assertCount(2, $this->repository->paginateAll());
        $this->assertCount(1, $this->repository->paginateAll('draft'));
        $this->assertCount(1, $this->repository->paginateAll(null, 'zebra unik'));
        $this->assertCount(1, $this->repository->paginateAll(null, null, 'Opini'));
    }

    public function test_all_for_user_and_all(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(2)->create(['user_id' => $user->id]);
        Post::factory()->count(3)->create();

        $this->assertCount(2, $this->repository->allForUser($user->id));
        $this->assertCount(5, $this->repository->all());
    }

    public function test_slug_exists(): void
    {
        $post = Post::factory()->create(['slug' => 'slug-terpakai']);

        $this->assertTrue($this->repository->slugExists('slug-terpakai'));
        $this->assertFalse($this->repository->slugExists('slug-bebas'));
        $this->assertFalse($this->repository->slugExists('slug-terpakai', $post->id));
    }

    public function test_create_update_delete(): void
    {
        $user = User::factory()->create();

        $post = $this->repository->create([
            'user_id' => $user->id,
            'title' => 'Judul',
            'slug' => 'judul',
            'category' => 'Umum',
            'content' => 'Isi',
        ]);
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Judul']);

        $this->assertTrue($this->repository->update($post, ['title' => 'Judul Baru']));
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Judul Baru']);

        $this->assertTrue($this->repository->delete($post));
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_get_stats_for_user_and_system(): void
    {
        $user = User::factory()->create();
        Post::factory()->published()->count(2)->create(['user_id' => $user->id]);
        Post::factory()->draft()->create(['user_id' => $user->id]);
        Post::factory()->published()->create();

        $this->assertSame(['total' => 3, 'published' => 2, 'draft' => 1], $this->repository->getStats($user->id));
        $this->assertSame(['total' => 4, 'published' => 3, 'draft' => 1], $this->repository->getStats());
    }
}

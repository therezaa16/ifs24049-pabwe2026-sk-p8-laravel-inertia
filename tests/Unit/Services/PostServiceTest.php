<?php

namespace Tests\Unit\Services;

use App\Features\Post\Models\Post;
use App\Features\Post\Repositories\PostRepository;
use App\Features\Post\Services\PostService;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PostService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->service = new PostService(new PostRepository());
    }

    public function test_create_post_defaults_to_draft_with_slug(): void
    {
        $user = User::factory()->create();

        $post = $this->service->createPost($user->id, ['title' => 'Judul Pertama']);

        $this->assertSame('judul-pertama', $post->slug);
        $this->assertSame('Umum', $post->category);
        $this->assertSame('', $post->content);
        $this->assertNull($post->excerpt);
        $this->assertFalse($post->is_published);
        $this->assertNull($post->published_at);
    }

    public function test_create_published_post_sets_published_at(): void
    {
        $user = User::factory()->create();

        $post = $this->service->createPost($user->id, [
            'title' => 'Terbit',
            'category' => 'Bisnis',
            'excerpt' => 'Ringkasan',
            'content' => 'Isi',
            'cover_image_path' => 'post-covers/a.png',
            'is_published' => true,
        ]);

        $this->assertTrue($post->is_published);
        $this->assertNotNull($post->published_at);
        $this->assertSame('Bisnis', $post->category);
        $this->assertSame('post-covers/a.png', $post->cover_image_path);
    }

    public function test_generate_unique_slug(): void
    {
        $user = User::factory()->create();

        $this->assertSame('judul-sama', $this->service->generateUniqueSlug('Judul Sama'));

        $this->service->createPost($user->id, ['title' => 'Judul Sama']);
        $this->assertSame('judul-sama-2', $this->service->generateUniqueSlug('Judul Sama'));

        $this->service->createPost($user->id, ['title' => 'Judul Sama']);
        $this->assertSame('judul-sama-3', $this->service->generateUniqueSlug('Judul Sama'));

        $this->assertSame('artikel', $this->service->generateUniqueSlug('!!!'));
    }

    public function test_update_post_keeps_slug_and_existing_values(): void
    {
        $post = Post::factory()->draft()->create(['title' => 'Lama', 'slug' => 'lama', 'category' => 'Opini', 'excerpt' => 'Ringkas', 'content' => 'Isi']);

        $this->assertTrue($this->service->updatePost($post, ['title' => 'Baru']));

        $post->refresh();
        $this->assertSame('Baru', $post->title);
        $this->assertSame('lama', $post->slug);
        $this->assertSame('Opini', $post->category);
        $this->assertSame('Ringkas', $post->excerpt);
        $this->assertSame('Isi', $post->content);
        $this->assertFalse($post->is_published);

        $this->service->updatePost($post, ['excerpt' => null, 'category' => 'Bisnis', 'content' => 'Isi Baru']);
        $post->refresh();
        $this->assertNull($post->excerpt);
        $this->assertSame('Bisnis', $post->category);
        $this->assertSame('Isi Baru', $post->content);
    }

    public function test_publication_attributes_follow_is_published(): void
    {
        $post = Post::factory()->draft()->create();

        $this->service->updatePost($post, ['is_published' => true]);
        $post->refresh();
        $this->assertTrue($post->is_published);
        $firstPublishedAt = $post->published_at;
        $this->assertNotNull($firstPublishedAt);

        // Already published: published_at is preserved
        $this->service->updatePost($post, ['is_published' => true]);
        $this->assertTrue($post->fresh()->published_at->equalTo($firstPublishedAt));

        $this->service->updatePost($post, ['is_published' => false]);
        $post->refresh();
        $this->assertFalse($post->is_published);
        $this->assertNull($post->published_at);
    }

    public function test_toggle_publish(): void
    {
        $post = Post::factory()->draft()->create();

        $this->assertTrue($this->service->togglePublish($post));
        $this->assertTrue($post->fresh()->is_published);

        $this->assertTrue($this->service->togglePublish($post->fresh()));
        $this->assertFalse($post->fresh()->is_published);
    }

    public function test_cover_photo_lifecycle(): void
    {
        $post = Post::factory()->create();

        $first = $this->service->updateCoverPhoto($post, UploadedFile::fake()->image('a.jpg'));
        Storage::disk('public')->assertExists($first);

        $second = $this->service->updateCoverPhoto($post->fresh(), UploadedFile::fake()->image('b.jpg'));
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->assertTrue($this->service->deleteCoverPhoto($post->fresh()));
        Storage::disk('public')->assertMissing($second);
        $this->assertNull($post->fresh()->cover_image_path);
    }

    public function test_delete_post_removes_cover_file(): void
    {
        $post = Post::factory()->create();
        $path = $this->service->updateCoverPhoto($post, UploadedFile::fake()->image('a.jpg'));

        $this->assertTrue($this->service->deletePost($post->fresh()));

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);

        // Post without cover
        $plain = Post::factory()->create();
        $this->assertTrue($this->service->deletePost($plain));
    }

    public function test_queries_are_delegated_to_repository(): void
    {
        $user = User::factory()->create();
        $published = Post::factory()->published()->create(['user_id' => $user->id, 'category' => 'Opini']);
        Post::factory()->published()->create(['category' => 'Opini']);
        Post::factory()->draft()->create(['user_id' => $user->id]);

        $this->assertCount(2, $this->service->getPublishedPosts());
        $this->assertSame($published->id, $this->service->getPublishedPostBySlug($published->slug)->id);
        $this->assertCount(1, $this->service->getRelatedPosts($published));
        $this->assertCount(2, $this->service->getPostsForUser($user->id));
        $this->assertCount(3, $this->service->getAllPosts());
        $this->assertCount(2, $this->service->getAllForUser($user->id));
        $this->assertCount(3, $this->service->getAllForExport());
        $this->assertSame(['total' => 2, 'published' => 1, 'draft' => 1], $this->service->getStatsForUser($user->id));
        $this->assertSame(['total' => 3, 'published' => 2, 'draft' => 1], $this->service->getSystemStats());

        $this->expectException(ModelNotFoundException::class);
        $this->service->getPublishedPostBySlug('tidak-ada');
    }
}

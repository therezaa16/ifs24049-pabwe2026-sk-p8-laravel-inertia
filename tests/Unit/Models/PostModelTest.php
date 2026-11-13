<?php

namespace Tests\Unit\Models;

use App\Features\Post\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $post->user);
        $this->assertSame($user->id, $post->user->id);
    }

    public function test_post_casts(): void
    {
        $post = Post::factory()->published()->create();

        $this->assertTrue($post->is_published);
        $this->assertInstanceOf(Carbon::class, $post->published_at);
    }

    public function test_factory_states(): void
    {
        $published = Post::factory()->published()->create();
        $this->assertTrue($published->is_published);
        $this->assertNotNull($published->published_at);

        $draft = Post::factory()->draft()->create();
        $this->assertFalse($draft->is_published);
        $this->assertNull($draft->published_at);
    }

    public function test_cover_image_url_returns_relative_url_or_null(): void
    {
        Storage::fake('public');

        $withoutCover = Post::factory()->create(['cover_image_path' => null]);
        $this->assertNull($withoutCover->coverImageUrl());

        $missingFile = Post::factory()->create(['cover_image_path' => 'post-covers/missing.png']);
        $this->assertNull($missingFile->coverImageUrl());

        Storage::disk('public')->put('post-covers/sample.png', 'content');
        $withCover = Post::factory()->create(['cover_image_path' => 'post-covers/sample.png']);
        $this->assertSame('/storage/post-covers/sample.png', $withCover->coverImageUrl());
    }

    public function test_reading_minutes(): void
    {
        $short = Post::factory()->make(['content' => 'Hanya beberapa kata']);
        $this->assertSame(1, $short->readingMinutes());

        $empty = Post::factory()->make(['content' => '']);
        $this->assertSame(1, $empty->readingMinutes());

        $long = Post::factory()->make(['content' => implode(' ', array_fill(0, 450, 'kata'))]);
        $this->assertSame(3, $long->readingMinutes());
    }

    public function test_to_list_item_and_to_detail(): void
    {
        $author = User::factory()->create(['name' => 'Penulis Uji', 'username' => 'penulis_uji']);
        $post = Post::factory()->published()->create([
            'user_id' => $author->id,
            'title' => 'Judul Uji',
            'category' => 'Pendidikan',
            'content' => '## Isi Lengkap',
        ]);

        $withoutAuthor = $post->fresh()->toListItem();
        $this->assertNull($withoutAuthor['author']);
        $this->assertSame('Pendidikan & Tutorial', $withoutAuthor['category_label']);
        $this->assertStringContainsString('emerald', $withoutAuthor['category_badge']);
        $this->assertArrayNotHasKey('content', $withoutAuthor);

        $detail = $post->fresh()->load('user')->toDetail();
        $this->assertSame('Penulis Uji', $detail['author']['name']);
        $this->assertSame('## Isi Lengkap', $detail['content']);
        $this->assertNotNull($detail['published_label']);
        $this->assertNotNull($detail['created_label']);
    }

    public function test_to_list_item_falls_back_for_unknown_category(): void
    {
        $post = Post::factory()->create(['category' => 'Kategori Lama']);

        $item = $post->toListItem();

        $this->assertSame('Kategori Lama', $item['category_label']);
        $this->assertStringContainsString('slate', $item['category_badge']);
        $this->assertNull($item['published_label']);
    }
}

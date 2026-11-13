<?php

namespace Tests\Feature\Post;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicPostControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_only_published_posts(): void
    {
        $author = User::factory()->member()->create(['name' => 'Penulis Satu']);
        Post::factory()->published()->create(['user_id' => $author->id, 'title' => 'Artikel Publik']);
        Post::factory()->draft()->create(['user_id' => $author->id, 'title' => 'Artikel Draf Rahasia']);

        $this->get('/articles')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/PostIndex')
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Artikel Publik')
                ->where('posts.data.0.author.name', 'Penulis Satu')
                ->has('categories', 6)
            );
    }

    public function test_search_by_title_and_content_is_case_insensitive(): void
    {
        Post::factory()->published()->create(['title' => 'Belajar Laravel Inertia', 'content' => 'isi biasa', 'excerpt' => 'ringkas']);
        Post::factory()->published()->create(['title' => 'Resep Nasi Goreng', 'content' => 'Gunakan KECAP manis', 'excerpt' => 'dapur']);
        Post::factory()->published()->create(['title' => 'Catatan Harian', 'content' => 'biasa', 'excerpt' => 'Membahas FILOSOFI hidup']);

        $this->get('/articles?search=laravel')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Belajar Laravel Inertia')
                ->where('filters.search', 'laravel')
            );

        $this->get('/articles?search=kecap')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Resep Nasi Goreng')
            );

        $this->get('/articles?search=filosofi')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Catatan Harian')
            );
    }

    public function test_filter_by_category(): void
    {
        Post::factory()->published()->create(['title' => 'Post Teknologi', 'category' => 'Teknologi']);
        Post::factory()->published()->create(['title' => 'Post Bisnis', 'category' => 'Bisnis']);

        $this->get('/articles?category=Bisnis')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Post Bisnis')
                ->where('filters.category', 'Bisnis')
            );
    }

    public function test_non_string_query_values_are_ignored(): void
    {
        Post::factory()->published()->count(2)->create();

        $this->get('/articles?search[]=a&category[]=b')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 2)
                ->where('filters.search', '')
                ->where('filters.category', '')
            );
    }

    public function test_reader_shows_published_post_with_related_articles(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'Artikel Utama',
            'slug' => 'artikel-utama',
            'category' => 'Opini',
            'content' => "## Judul\n\nIsi **markdown**.",
        ]);
        Post::factory()->published()->create(['title' => 'Terkait Satu', 'category' => 'Opini']);
        Post::factory()->published()->create(['title' => 'Beda Kategori', 'category' => 'Bisnis']);
        Post::factory()->draft()->create(['title' => 'Terkait Draf', 'category' => 'Opini']);

        $this->get("/articles/{$post->slug}")
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/PostShow')
                ->where('post.title', 'Artikel Utama')
                ->where('post.content', "## Judul\n\nIsi **markdown**.")
                ->has('related', 1)
                ->where('related.0.title', 'Terkait Satu')
            );
    }

    public function test_draft_or_missing_post_returns_404(): void
    {
        $draft = Post::factory()->draft()->create(['slug' => 'masih-draf']);

        $this->get("/articles/{$draft->slug}")->assertStatus(404);
        $this->get('/articles/tidak-ada')->assertStatus(404);
    }
}

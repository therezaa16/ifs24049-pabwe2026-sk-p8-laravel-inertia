<?php

namespace Tests\Feature\Post;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedPostItem;
use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get('/dashboard/posts')->assertRedirect(route('login'));
    }

    public function test_member_sees_only_own_posts_with_stats(): void
    {
        $user = User::factory()->member()->create();
        Post::factory()->published()->create(['user_id' => $user->id, 'title' => 'Artikel Saya Terbit']);
        Post::factory()->draft()->create(['user_id' => $user->id, 'title' => 'Artikel Saya Draf']);
        Post::factory()->published()->create(['title' => 'Artikel Orang Lain']);

        $this->actingAs($user)
            ->get('/dashboard/posts')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Post/Index')
                ->has('posts.data', 2)
                ->where('stats.total', 2)
                ->where('stats.published', 1)
                ->where('stats.draft', 1)
                ->has('categories', 6)
            );
    }

    public function test_dashboard_search_status_and_category_filters(): void
    {
        $user = User::factory()->member()->create();
        Post::factory()->published()->create(['user_id' => $user->id, 'title' => 'Makan Pagi', 'category' => 'Gaya Hidup']);
        Post::factory()->draft()->create(['user_id' => $user->id, 'title' => 'Olahraga Malam', 'category' => 'Umum']);

        $this->actingAs($user)->get('/dashboard/posts?search=Makan')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Makan Pagi')
            );

        $this->actingAs($user)->get('/dashboard/posts?status=draft')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Olahraga Malam')
            );

        $this->actingAs($user)->get('/dashboard/posts?status=published&category=Gaya Hidup')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Makan Pagi')
                ->where('filters.status', 'published')
                ->where('filters.category', 'Gaya Hidup')
            );
    }

    public function test_dashboard_ignores_non_string_filters(): void
    {
        $user = User::factory()->member()->create();
        Post::factory()->count(2)->create(['user_id' => $user->id]);

        $this->actingAs($user)->get('/dashboard/posts?search[]=x&status[]=y&category[]=z')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 2)
                ->where('filters.search', '')
                ->where('filters.status', '')
                ->where('filters.category', '')
            );
    }

    public function test_store_validation_and_success(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)
            ->post('/dashboard/posts', ['title' => '', 'category' => 'Tidak Ada', 'content' => ''])
            ->assertSessionHasErrors(['title', 'category', 'content']);

        $this->actingAs($user)
            ->post('/dashboard/posts', [
                'title' => 'Artikel Baru Saya',
                'category' => 'Teknologi',
                'excerpt' => 'Ringkasan singkat',
                'content' => '## Isi Artikel',
                'is_published' => true,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $post = Post::where('title', 'Artikel Baru Saya')->firstOrFail();
        $this->assertSame($user->id, $post->user_id);
        $this->assertSame('artikel-baru-saya', $post->slug);
        $this->assertTrue($post->is_published);
        $this->assertNotNull($post->published_at);
    }

    public function test_store_with_cover_photo(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)
            ->post('/dashboard/posts', [
                'title' => 'Artikel Bersampul',
                'category' => 'Umum',
                'content' => 'Isi',
                'coverPhoto' => UploadedFile::fake()->image('cover.jpg', 600, 400),
            ])
            ->assertSessionHasNoErrors();

        $post = Post::where('title', 'Artikel Bersampul')->firstOrFail();
        $this->assertNotNull($post->cover_image_path);
        Storage::disk('public')->assertExists($post->cover_image_path);
    }

    public function test_show_post_detail(): void
    {
        $user = User::factory()->member()->create();
        $post = Post::factory()->create(['user_id' => $user->id, 'title' => 'Detail Artikel']);

        $this->actingAs($user)
            ->get("/dashboard/posts/{$post->id}")
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Post/Show')
                ->where('post.title', 'Detail Artikel')
                ->has('post.content')
            );
    }

    public function test_update_post(): void
    {
        $user = User::factory()->member()->create();
        $post = Post::factory()->draft()->create(['user_id' => $user->id, 'title' => 'Judul Lama', 'slug' => 'judul-lama']);

        $this->actingAs($user)
            ->put("/dashboard/posts/{$post->id}", [
                'title' => 'Judul Baru',
                'category' => 'Opini',
                'excerpt' => 'Ringkasan baru',
                'content' => 'Isi baru',
                'is_published' => true,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $post->refresh();
        $this->assertSame('Judul Baru', $post->title);
        $this->assertSame('judul-lama', $post->slug);
        $this->assertSame('Opini', $post->category);
        $this->assertTrue($post->is_published);
    }

    public function test_update_validation_errors(): void
    {
        $user = User::factory()->member()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->put("/dashboard/posts/{$post->id}", ['title' => '', 'category' => 'X', 'content' => ''])
            ->assertSessionHasErrors(['title', 'category', 'content']);
    }

    public function test_toggle_publish(): void
    {
        $user = User::factory()->member()->create();
        $post = Post::factory()->draft()->create(['user_id' => $user->id]);

        $this->actingAs($user)->patch("/dashboard/posts/{$post->id}/publish")->assertSessionHas('success');
        $this->assertTrue($post->fresh()->is_published);
        $this->assertNotNull($post->fresh()->published_at);

        $this->actingAs($user)->patch("/dashboard/posts/{$post->id}/publish")->assertSessionHas('success');
        $this->assertFalse($post->fresh()->is_published);
        $this->assertNull($post->fresh()->published_at);
    }

    public function test_update_and_delete_cover_photo(): void
    {
        $user = User::factory()->member()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post("/dashboard/posts/{$post->id}/cover", ['coverPhoto' => UploadedFile::fake()->image('cover.jpg', 600, 400)])
            ->assertSessionHas('success');

        $post->refresh();
        $this->assertNotNull($post->cover_image_path);
        Storage::disk('public')->assertExists($post->cover_image_path);

        $this->actingAs($user)
            ->post("/dashboard/posts/{$post->id}/cover", [])
            ->assertSessionHasErrors('coverPhoto');

        $this->actingAs($user)->delete("/dashboard/posts/{$post->id}/cover")->assertSessionHas('success');

        $this->assertNull($post->fresh()->cover_image_path);
    }

    public function test_delete_post(): void
    {
        $user = User::factory()->member()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete("/dashboard/posts/{$post->id}")
            ->assertRedirect(route('posts.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_export_excel(): void
    {
        $user = User::factory()->member()->create();
        Post::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/dashboard/posts/export');

        $this->assertInstanceOf(StreamedResponse::class, $response->baseResponse);
        $this->assertStringContainsString('spreadsheetml', (string) $response->headers->get('content-type'));
    }

    public function test_import_excel(): void
    {
        $user = User::factory()->member()->create();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['No', 'Judul', 'Kategori', 'Ringkasan', 'Konten', 'Status'],
            [1, 'Artikel Impor 1', 'Bisnis', 'Ringkas', '## Isi', 'Dipublikasikan'],
            [2, 'Artikel Impor 2', 'Tidak Dikenal', null, 'Isi dua', 'Draf'],
        ]);

        $tempFile = tempnam(sys_get_temp_dir(), 'test_excel_').'.xlsx';
        (new Xlsx($spreadsheet))->save($tempFile);

        $uploaded = new UploadedFile($tempFile, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->actingAs($user)
            ->post('/dashboard/posts/import', ['importFile' => $uploaded])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('posts', ['user_id' => $user->id, 'title' => 'Artikel Impor 1', 'category' => 'Bisnis', 'is_published' => true]);
        $this->assertDatabaseHas('posts', ['user_id' => $user->id, 'title' => 'Artikel Impor 2', 'category' => 'Umum', 'is_published' => false]);

        @unlink($tempFile);
    }

    public function test_import_requires_valid_file(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)->post('/dashboard/posts/import', [])->assertSessionHasErrors('importFile');
        $this->actingAs($user)
            ->post('/dashboard/posts/import', ['importFile' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('importFile');
    }

    public function test_generate_ai_success_flashes_items(): void
    {
        $user = User::factory()->member()->create();

        $stubAi = $this->createStub(AiClientInterface::class);
        $stubAi->method('generatePosts')->willReturn([
            new GeneratedPostItem('Artikel dari AI', 'Teknologi', 'Ringkasan AI', '## Isi AI'),
        ]);
        $this->app->instance(AiClientInterface::class, $stubAi);

        $this->actingAs($user)
            ->post('/dashboard/posts/ai-generate', ['prompt' => 'Tulis tentang Laravel', 'count' => 1])
            ->assertSessionHas('aiGeneratedItems', [
                ['title' => 'Artikel dari AI', 'category' => 'Teknologi', 'excerpt' => 'Ringkasan AI', 'content' => '## Isi AI'],
            ]);

        // count bersifat opsional (default 3)
        $this->actingAs($user)
            ->post('/dashboard/posts/ai-generate', ['prompt' => 'Tulis tentang React'])
            ->assertSessionHas('aiGeneratedItems');
    }

    public function test_generate_ai_validation_limits(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)->post('/dashboard/posts/ai-generate', ['prompt' => ''])->assertSessionHasErrors('prompt');
        $this->actingAs($user)->post('/dashboard/posts/ai-generate', ['prompt' => 'Topik valid', 'count' => 0])->assertSessionHasErrors('count');
        $this->actingAs($user)->post('/dashboard/posts/ai-generate', ['prompt' => 'Topik valid', 'count' => 6])->assertSessionHasErrors('count');
    }

    public function test_generate_ai_failure_flashes_error(): void
    {
        $user = User::factory()->member()->create();

        $stubAi = $this->createStub(AiClientInterface::class);
        $stubAi->method('generatePosts')->willThrowException(new \RuntimeException('Kunci API salah'));
        $this->app->instance(AiClientInterface::class, $stubAi);

        $this->actingAs($user)
            ->post('/dashboard/posts/ai-generate', ['prompt' => 'Tulis tentang Laravel', 'count' => 2])
            ->assertSessionHas('error', 'Gagal menghasilkan artikel dengan AI: Kunci API salah');
    }

    public function test_save_ai_creates_drafts(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)
            ->post('/dashboard/posts/ai-save', [
                'items' => [
                    ['title' => 'AI Item 1', 'category' => 'Bisnis', 'excerpt' => 'Ringkas', 'content' => 'Isi 1'],
                    ['title' => 'AI Item 2', 'category' => null, 'excerpt' => null, 'content' => 'Isi 2'],
                ],
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('posts', ['user_id' => $user->id, 'title' => 'AI Item 1', 'category' => 'Bisnis', 'is_published' => false]);
        $this->assertDatabaseHas('posts', ['user_id' => $user->id, 'title' => 'AI Item 2', 'category' => 'Umum', 'is_published' => false]);
    }

    public function test_save_ai_validation(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)->post('/dashboard/posts/ai-save', [])->assertSessionHasErrors('items');
        $this->actingAs($user)
            ->post('/dashboard/posts/ai-save', ['items' => [['title' => '', 'content' => '']]])
            ->assertSessionHasErrors(['items.0.title', 'items.0.content']);
    }

    public function test_member_cannot_access_other_members_post(): void
    {
        $user = User::factory()->member()->create();
        $other = Post::factory()->create();

        $this->actingAs($user);

        $this->get("/dashboard/posts/{$other->id}")->assertStatus(403);
        $this->put("/dashboard/posts/{$other->id}", ['title' => 'Hacked', 'category' => 'Umum', 'content' => 'x'])->assertStatus(403);
        $this->patch("/dashboard/posts/{$other->id}/publish")->assertStatus(403);
        $this->delete("/dashboard/posts/{$other->id}")->assertStatus(403);
        $this->post("/dashboard/posts/{$other->id}/cover", ['coverPhoto' => UploadedFile::fake()->image('c.jpg')])->assertStatus(403);
        $this->delete("/dashboard/posts/{$other->id}/cover")->assertStatus(403);
    }

    public function test_admin_can_view_but_not_modify_member_post(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create(['title' => 'Milik Member']);

        $this->actingAs($admin)
            ->get("/dashboard/posts/{$post->id}")
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->where('post.title', 'Milik Member'));

        $this->actingAs($admin)->delete("/dashboard/posts/{$post->id}")->assertStatus(403);
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }
}

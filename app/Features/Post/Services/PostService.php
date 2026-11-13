<?php

namespace App\Features\Post\Services;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostService
{
    public function __construct(
        protected PostRepositoryInterface $postRepository
    ) {}

    /**
     * Get paginated published posts for the public pages.
     */
    public function getPublishedPosts(?string $search = null, ?string $category = null, int $perPage = 9): LengthAwarePaginator
    {
        return $this->postRepository->paginatePublished($search, $category, $perPage);
    }

    /**
     * Get a single published post by slug (404 when missing or still a draft).
     */
    public function getPublishedPostBySlug(string $slug): Post
    {
        return $this->postRepository->findPublishedBySlugOrFail($slug);
    }

    /**
     * Get related published posts (same category).
     *
     * @return Collection<int, Post>
     */
    public function getRelatedPosts(Post $post, int $limit = 3): Collection
    {
        return $this->postRepository->relatedPublished($post, $limit);
    }

    /**
     * Get paginated posts for the specified author.
     */
    public function getPostsForUser(int $userId, ?string $status = null, ?string $search = null, ?string $category = null, int $perPage = 8): LengthAwarePaginator
    {
        return $this->postRepository->forUser($userId, $status, $search, $category, $perPage);
    }

    /**
     * Get paginated posts of the whole system (admin monitoring).
     */
    public function getAllPosts(?string $status = null, ?string $search = null, ?string $category = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->postRepository->paginateAll($status, $search, $category, $perPage);
    }

    /**
     * Get every post of an author (no pagination) for Excel export.
     *
     * @return Collection<int, Post>
     */
    public function getAllForUser(int $userId): Collection
    {
        return $this->postRepository->allForUser($userId);
    }

    /**
     * Get every post of the system (no pagination) for Excel export.
     *
     * @return Collection<int, Post>
     */
    public function getAllForExport(): Collection
    {
        return $this->postRepository->all();
    }

    /**
     * Create a new post for the user. A unique slug is generated from the title.
     *
     * @param array<string, mixed> $data
     */
    public function createPost(int $userId, array $data): Post
    {
        $isPublished = (bool) ($data['is_published'] ?? false);

        return $this->postRepository->create([
            'user_id' => $userId,
            'title' => $data['title'],
            'slug' => $this->generateUniqueSlug($data['title']),
            'category' => $data['category'] ?? PostCategory::UMUM->value,
            'excerpt' => $data['excerpt'] ?? null,
            'content' => $data['content'] ?? '',
            'cover_image_path' => $data['cover_image_path'] ?? null,
            'is_published' => $isPublished,
            'published_at' => $isPublished ? now() : null,
        ]);
    }

    /**
     * Update an existing post. The slug stays stable so published URLs never break.
     *
     * @param array<string, mixed> $data
     */
    public function updatePost(Post $post, array $data): bool
    {
        $attributes = [
            'title' => $data['title'] ?? $post->title,
            'category' => $data['category'] ?? $post->category,
            'excerpt' => array_key_exists('excerpt', $data) ? $data['excerpt'] : $post->excerpt,
            'content' => $data['content'] ?? $post->content,
            'cover_image_path' => array_key_exists('cover_image_path', $data) ? $data['cover_image_path'] : $post->cover_image_path,
        ];

        if (array_key_exists('is_published', $data)) {
            $attributes = [...$attributes, ...$this->publicationAttributes($post, (bool) $data['is_published'])];
        }

        return $this->postRepository->update($post, $attributes);
    }

    /**
     * Toggle publication status between Draft and Published.
     */
    public function togglePublish(Post $post): bool
    {
        return $this->updatePost($post, ['is_published' => !$post->is_published]);
    }

    /**
     * Upload (or replace) the cover photo of the post.
     */
    public function updateCoverPhoto(Post $post, mixed $file): string
    {
        $this->removeCoverFile($post);

        $path = $file->store('post-covers', 'public');
        $this->updatePost($post, ['cover_image_path' => $path]);

        return $path;
    }

    /**
     * Remove the cover photo of the post.
     */
    public function deleteCoverPhoto(Post $post): bool
    {
        $this->removeCoverFile($post);

        return $this->updatePost($post, ['cover_image_path' => null]);
    }

    /**
     * Delete a post together with its cover photo.
     */
    public function deletePost(Post $post): bool
    {
        $this->removeCoverFile($post);

        return $this->postRepository->delete($post);
    }

    /**
     * Statistics summary (total / published / draft) for an author.
     *
     * @return array{total: int, published: int, draft: int}
     */
    public function getStatsForUser(int $userId): array
    {
        return $this->postRepository->getStats($userId);
    }

    /**
     * Statistics summary for the entire system.
     *
     * @return array{total: int, published: int, draft: int}
     */
    public function getSystemStats(): array
    {
        return $this->postRepository->getStats();
    }

    /**
     * Generate a unique, URL friendly slug for the given title.
     */
    public function generateUniqueSlug(string $title): string
    {
        $base = Str::limit(Str::slug($title), 200, '');
        $base = $base !== '' ? $base : 'artikel';

        $slug = $base;
        $suffix = 2;

        while ($this->postRepository->slugExists($slug)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * Build the publication attributes while keeping published_at consistent.
     *
     * @return array{is_published: bool, published_at: mixed}
     */
    protected function publicationAttributes(Post $post, bool $publish): array
    {
        return [
            'is_published' => $publish,
            'published_at' => $publish ? ($post->published_at ?? now()) : null,
        ];
    }

    /**
     * Delete the stored cover file when it exists.
     */
    protected function removeCoverFile(Post $post): void
    {
        if ($post->cover_image_path && Storage::disk('public')->exists($post->cover_image_path)) {
            Storage::disk('public')->delete($post->cover_image_path);
        }
    }
}

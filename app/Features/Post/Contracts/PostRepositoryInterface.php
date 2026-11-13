<?php

namespace App\Features\Post\Contracts;

use App\Features\Post\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PostRepositoryInterface
{
    /**
     * Paginate published posts for the public pages (search + category filter).
     */
    public function paginatePublished(?string $search = null, ?string $category = null, int $perPage = 9): LengthAwarePaginator;

    /**
     * Find a published post by slug or throw a 404 exception.
     */
    public function findPublishedBySlugOrFail(string $slug): Post;

    /**
     * Get other published posts of the same category.
     *
     * @return Collection<int, Post>
     */
    public function relatedPublished(Post $post, int $limit = 3): Collection;

    /**
     * Paginate posts owned by a user with optional status/search/category filters.
     */
    public function forUser(int $userId, ?string $status = null, ?string $search = null, ?string $category = null, int $perPage = 10): LengthAwarePaginator;

    /**
     * Paginate every post in the system (admin monitoring).
     */
    public function paginateAll(?string $status = null, ?string $search = null, ?string $category = null, int $perPage = 10): LengthAwarePaginator;

    /**
     * Get all posts of a user without pagination.
     *
     * @return Collection<int, Post>
     */
    public function allForUser(int $userId): Collection;

    /**
     * Get all posts in the system (with authors) without pagination.
     *
     * @return Collection<int, Post>
     */
    public function all(): Collection;

    /**
     * Determine whether a slug is already used (optionally ignoring a post id).
     */
    public function slugExists(string $slug, ?int $ignoreId = null): bool;

    /**
     * Create a new post.
     *
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): Post;

    /**
     * Update an existing post.
     *
     * @param array<string, mixed> $attributes
     */
    public function update(Post $post, array $attributes): bool;

    /**
     * Delete an existing post.
     */
    public function delete(Post $post): bool;

    /**
     * Get total / published / draft counters (system-wide when no user is given).
     *
     * @return array{total: int, published: int, draft: int}
     */
    public function getStats(?int $userId = null): array;
}

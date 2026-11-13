<?php

namespace App\Features\Post\Repositories;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PostRepository implements PostRepositoryInterface
{
    public function paginatePublished(?string $search = null, ?string $category = null, int $perPage = 9): LengthAwarePaginator
    {
        $query = Post::query()
            ->with('user:id,name,username')
            ->where('is_published', true);

        $this->applyFilters($query, $search, $category);

        return $query->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findPublishedBySlugOrFail(string $slug): Post
    {
        return Post::query()
            ->with('user:id,name,username')
            ->where('is_published', true)
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function relatedPublished(Post $post, int $limit = 3): Collection
    {
        return Post::query()
            ->with('user:id,name,username')
            ->where('is_published', true)
            ->where('category', $post->category)
            ->where('id', '!=', $post->id)
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    public function forUser(int $userId, ?string $status = null, ?string $search = null, ?string $category = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = Post::query()->where('user_id', $userId);

        $this->applyStatus($query, $status);
        $this->applyFilters($query, $search, $category);

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    public function paginateAll(?string $status = null, ?string $search = null, ?string $category = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = Post::query()->with('user:id,name,username');

        $this->applyStatus($query, $status);
        $this->applyFilters($query, $search, $category);

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    public function allForUser(int $userId): Collection
    {
        return Post::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->get();
    }

    public function all(): Collection
    {
        return Post::query()
            ->with('user:id,name,username')
            ->latest('id')
            ->get();
    }

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return Post::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn (Builder $query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    public function create(array $attributes): Post
    {
        return Post::create($attributes);
    }

    public function update(Post $post, array $attributes): bool
    {
        return $post->update($attributes);
    }

    public function delete(Post $post): bool
    {
        return (bool) $post->delete();
    }

    public function getStats(?int $userId = null): array
    {
        $base = Post::query()->when($userId, fn (Builder $query) => $query->where('user_id', $userId));

        $total = (clone $base)->count();
        $published = (clone $base)->where('is_published', true)->count();

        return [
            'total' => $total,
            'published' => $published,
            'draft' => $total - $published,
        ];
    }

    /**
     * Apply the "published" / "draft" status filter.
     *
     * @param Builder<Post> $query
     */
    protected function applyStatus(Builder $query, ?string $status): void
    {
        if ($status === 'published') {
            $query->where('is_published', true);
        } elseif ($status === 'draft') {
            $query->where('is_published', false);
        }
    }

    /**
     * Apply the case-insensitive search (title/excerpt/content) and category filters.
     * LOWER() + LIKE keeps behaviour identical on PostgreSQL and SQLite.
     *
     * @param Builder<Post> $query
     */
    protected function applyFilters(Builder $query, ?string $search, ?string $category): void
    {
        if (!empty($category)) {
            $query->where('category', $category);
        }

        if (!empty($search)) {
            $term = '%'.mb_strtolower($search).'%';

            $query->where(function (Builder $q) use ($term) {
                $q->whereRaw('LOWER(title) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(excerpt) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(content) LIKE ?', [$term]);
            });
        }
    }
}

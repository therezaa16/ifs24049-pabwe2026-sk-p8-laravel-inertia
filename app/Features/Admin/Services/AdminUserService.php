<?php

namespace App\Features\Admin\Services;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AdminUserService
{
    public function __construct(
        protected PostRepositoryInterface $postRepository
    ) {}

    /**
     * Get paginated list of all users with their posts count.
     */
    public function getUsersList(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = User::query()->withCount('posts');

        if (!empty($search)) {
            $term = '%'.mb_strtolower($search).'%';

            $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(username) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$term]);
            });
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    /**
     * Get read-only list of posts for a specific target user.
     */
    public function getUserPosts(int $userId, ?string $status = null, ?string $search = null, ?string $category = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->postRepository->forUser($userId, $status, $search, $category, $perPage);
    }

    /**
     * Get every post of a user (no pagination) for Excel export.
     *
     * @return Collection<int, \App\Features\Post\Models\Post>
     */
    public function getAllUserPosts(int $userId): Collection
    {
        return $this->postRepository->allForUser($userId);
    }
}

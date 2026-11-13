<?php

namespace App\Features\Admin\Controllers;

use App\Features\Admin\Services\AdminUserService;
use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use App\Features\Post\Services\PostExcelService;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminUserController
{
    public function index(Request $request, AdminUserService $adminService): Response
    {
        $search = $request->query('search');
        $users = $adminService->getUsersList(is_string($search) && $search !== '' ? $search : null, 10);

        return Inertia::render('Admin/UserList', [
            'users' => $users,
            'filters' => [
                'search' => is_string($search) ? $search : '',
            ],
        ]);
    }

    public function posts(Request $request, User $user, AdminUserService $adminService): Response
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $category = $request->query('category');

        $posts = $adminService->getUserPosts(
            $user->id,
            is_string($status) && $status !== '' ? $status : null,
            is_string($search) && $search !== '' ? $search : null,
            is_string($category) && $category !== '' ? $category : null,
            10
        );

        return Inertia::render('Admin/UserPostList', [
            'targetUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'posts' => $posts->through(fn (Post $post) => $post->toListItem()),
            'categories' => PostCategory::options(),
            'filters' => [
                'search' => is_string($search) ? $search : '',
                'status' => is_string($status) ? $status : '',
                'category' => is_string($category) ? $category : '',
            ],
        ]);
    }

    public function exportUserPosts(User $user, AdminUserService $adminService, PostExcelService $excelService): StreamedResponse
    {
        $safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', $user->name);

        return $excelService->exportToResponse(
            $adminService->getAllUserPosts($user->id),
            "artikel-{$safeName}-".now()->format('Ymd-His').'.xlsx'
        );
    }
}

<?php

namespace App\Features\Post\Controllers;

use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use App\Features\Post\Services\PostExcelService;
use App\Features\Post\Services\PostService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminPostController
{
    /**
     * Read-only monitoring of every post in the system.
     */
    public function index(Request $request, PostService $service): Response
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $category = $request->query('category');

        $posts = $service->getAllPosts(
            is_string($status) && $status !== '' ? $status : null,
            is_string($search) && $search !== '' ? $search : null,
            is_string($category) && $category !== '' ? $category : null,
            10
        );

        return Inertia::render('Admin/PostList', [
            'posts' => $posts->through(fn (Post $post) => $post->toListItem()),
            'categories' => PostCategory::options(),
            'filters' => [
                'search' => is_string($search) ? $search : '',
                'status' => is_string($status) ? $status : '',
                'category' => is_string($category) ? $category : '',
            ],
            'stats' => $service->getSystemStats(),
        ]);
    }

    /**
     * Export every post in the system to Excel.
     */
    public function export(PostService $service, PostExcelService $excelService): StreamedResponse
    {
        return $excelService->exportToResponse(
            $service->getAllForExport(),
            'semua-artikel-'.now()->format('Ymd-His').'.xlsx',
            true
        );
    }
}

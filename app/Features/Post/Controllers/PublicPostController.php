<?php

namespace App\Features\Post\Controllers;

use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use App\Features\Post\Services\PostService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicPostController
{
    /**
     * Public home / article gallery with search and category filter.
     */
    public function index(Request $request, PostService $service): Response
    {
        $search = $request->query('search');
        $category = $request->query('category');

        $posts = $service->getPublishedPosts(
            is_string($search) ? $search : null,
            is_string($category) ? $category : null,
            9
        );

        return Inertia::render('Public/PostIndex', [
            'posts' => $posts->through(fn (Post $post) => $post->toListItem()),
            'categories' => PostCategory::options(),
            'filters' => [
                'search' => is_string($search) ? $search : '',
                'category' => is_string($category) ? $category : '',
            ],
            'isHome' => $request->routeIs('home'),
        ]);
    }

    /**
     * Read a single published article (Markdown rendered on the client).
     */
    public function show(string $slug, PostService $service): Response
    {
        $post = $service->getPublishedPostBySlug($slug);

        return Inertia::render('Public/PostShow', [
            'post' => $post->toDetail(),
            'related' => $service->getRelatedPosts($post, 3)
                ->map(fn (Post $related) => $related->toListItem())
                ->values(),
        ]);
    }
}

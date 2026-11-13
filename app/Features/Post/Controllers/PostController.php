<?php

namespace App\Features\Post\Controllers;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use App\Features\Post\Services\PostExcelService;
use App\Features\Post\Services\PostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostController
{
    public function index(Request $request, PostService $service): Response
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $category = $request->query('category');

        $posts = $service->getPostsForUser(
            (int) Auth::id(),
            is_string($status) && $status !== '' ? $status : null,
            is_string($search) && $search !== '' ? $search : null,
            is_string($category) && $category !== '' ? $category : null,
            8
        );

        return Inertia::render('Post/Index', [
            'posts' => $posts->through(fn (Post $post) => $post->toListItem()),
            'categories' => PostCategory::options(),
            'filters' => [
                'search' => is_string($search) ? $search : '',
                'status' => is_string($status) ? $status : '',
                'category' => is_string($category) ? $category : '',
            ],
            'stats' => $service->getStatsForUser((int) Auth::id()),
        ]);
    }

    public function store(Request $request, PostService $service): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(PostCategory::class)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'is_published' => ['nullable', 'boolean'],
            'coverPhoto' => ['nullable', 'image', 'max:5120'],
        ]);

        $post = $service->createPost((int) Auth::id(), $validated);

        if ($request->hasFile('coverPhoto')) {
            $service->updateCoverPhoto($post, $request->file('coverPhoto'));
        }

        return back()->with('success', 'Artikel baru berhasil ditambahkan.');
    }

    public function show(Post $post): Response
    {
        Gate::authorize('view', $post);

        return Inertia::render('Post/Show', [
            'post' => $post->load('user:id,name,username')->toDetail(),
            'categories' => PostCategory::options(),
        ]);
    }

    public function update(Request $request, Post $post, PostService $service): RedirectResponse
    {
        Gate::authorize('update', $post);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(PostCategory::class)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $service->updatePost($post, $validated);

        return back()->with('success', 'Artikel berhasil diperbarui.');
    }

    public function destroy(Post $post, PostService $service): RedirectResponse
    {
        Gate::authorize('delete', $post);

        $service->deletePost($post);

        return redirect()->route('posts.index')->with('success', 'Artikel berhasil dihapus.');
    }

    public function publish(Post $post, PostService $service): RedirectResponse
    {
        Gate::authorize('update', $post);

        $service->togglePublish($post);

        return back()->with('success', 'Status publikasi artikel berhasil diperbarui.');
    }

    public function updateCover(Request $request, Post $post, PostService $service): RedirectResponse
    {
        Gate::authorize('update', $post);

        $request->validate([
            'coverPhoto' => ['required', 'image', 'max:5120'],
        ]);

        $service->updateCoverPhoto($post, $request->file('coverPhoto'));

        return back()->with('success', 'Foto sampul berhasil diperbarui.');
    }

    public function deleteCover(Post $post, PostService $service): RedirectResponse
    {
        Gate::authorize('update', $post);

        $service->deleteCoverPhoto($post);

        return back()->with('success', 'Foto sampul berhasil dihapus.');
    }

    public function exportExcel(PostService $service, PostExcelService $excelService): StreamedResponse
    {
        $posts = $service->getAllForUser((int) Auth::id());

        return $excelService->exportToResponse($posts, 'artikel-'.now()->format('Ymd-His').'.xlsx');
    }

    public function importExcel(Request $request, PostExcelService $excelService): RedirectResponse
    {
        $request->validate([
            'importFile' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $result = $excelService->import($request->file('importFile')->getRealPath(), (int) Auth::id());

        return back()->with('success', "Berhasil mengimpor {$result['imported']} artikel (dilewati: {$result['skipped']}).");
    }

    public function generateAi(Request $request, AiClientInterface $aiClient): RedirectResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'min:3', 'max:1000'],
            'count' => ['nullable', 'integer', 'min:1', 'max:5'],
        ], [
            'prompt.required' => 'Topik / instruksi artikel harus diisi minimal 3 karakter.',
            'prompt.min' => 'Topik / instruksi artikel harus minimal 3 karakter.',
            'count.min' => 'Jumlah artikel minimal 1.',
            'count.max' => 'Jumlah artikel maksimal 5 per sekali generate.',
            'count.integer' => 'Jumlah artikel harus berupa angka bulat.',
        ]);

        try {
            $items = $aiClient->generatePosts($validated['prompt'], (int) ($validated['count'] ?? 3));
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghasilkan artikel dengan AI: '.$e->getMessage());
        }

        return back()->with('aiGeneratedItems', array_map(fn ($item) => $item->toArray(), $items));
    }

    public function saveAi(Request $request, PostService $service): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.category' => ['nullable', Rule::enum(PostCategory::class)],
            'items.*.excerpt' => ['nullable', 'string', 'max:500'],
            'items.*.content' => ['required', 'string'],
        ]);

        foreach ($validated['items'] as $item) {
            $service->createPost((int) Auth::id(), [
                'title' => $item['title'],
                'category' => $item['category'] ?? PostCategory::UMUM->value,
                'excerpt' => $item['excerpt'] ?? null,
                'content' => $item['content'],
                'is_published' => false,
            ]);
        }

        $count = count($validated['items']);

        return back()->with('success', "Berhasil menyimpan {$count} artikel draf hasil AI.");
    }
}

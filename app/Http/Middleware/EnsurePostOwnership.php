<?php

namespace App\Http\Middleware;

use App\Features\Post\Models\Post;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePostOwnership
{
    /**
     * Ensure members never touch other users' posts and nobody (even admin) modifies them.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $currentUser = $request->user();

        if (!$currentUser) {
            abort(401, 'Unauthenticated');
        }

        // Route has a {user} parameter (e.g. /admin/users/{user}/posts)
        $routeUser = $request->route('user');
        if ($routeUser) {
            $targetUserId = $routeUser instanceof User ? $routeUser->id : (int) $routeUser;

            if ($currentUser->isMember() && $currentUser->id !== $targetUserId) {
                abort(403, 'Akses ditolak: Member tidak dapat mengakses artikel orang lain.');
            }
        }

        // Route has a {post} parameter (e.g. /dashboard/posts/{post})
        $routePost = $request->route('post');
        if ($routePost) {
            $post = $routePost instanceof Post ? $routePost : Post::find($routePost);

            if ($post) {
                if ($currentUser->isMember() && $currentUser->id !== $post->user_id) {
                    abort(403, 'Akses ditolak: Member tidak dapat mengakses artikel orang lain.');
                }

                // Write methods (PUT, PATCH, DELETE, POST): only the owner, even for admins.
                if ($request->isMethodSafe() === false && $currentUser->id !== $post->user_id) {
                    abort(403, 'Akses ditolak: Hanya pemilik artikel yang dapat mengubah artikel ini.');
                }
            }
        }

        return $next($request);
    }
}

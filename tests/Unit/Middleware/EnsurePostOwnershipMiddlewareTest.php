<?php

namespace Tests\Unit\Middleware;

use App\Features\Post\Models\Post;
use App\Http\Middleware\EnsurePostOwnership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsurePostOwnershipMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocks_unauthenticated_request(): void
    {
        $middleware = new EnsurePostOwnership();
        $request = Request::create('/posts', 'GET');

        $this->expectException(HttpException::class);
        $middleware->handle($request, fn () => new Response('OK'));
    }

    public function test_route_user_parameter_ownership(): void
    {
        $middleware = new EnsurePostOwnership();
        $member1 = User::factory()->member()->create();
        $member2 = User::factory()->member()->create();
        $admin = User::factory()->admin()->create();

        // Member accessing own
        $req1 = Request::create('/admin/users/'.$member1->id.'/posts', 'GET');
        $req1->setUserResolver(fn () => $member1);
        $req1->setRouteResolver(fn () => new class($member1) {
            public function __construct(public $user) {}
            public function parameter($name) { return $this->user; }
            public function route($name) { return $this->user; }
        });
        // We can bind route parameters using Laravel's Route
        $req1 = Request::create('/test', 'GET');
        $req1->setUserResolver(fn () => $member1);
        $route = new \Illuminate\Routing\Route('GET', '/test/{user}', []);
        $route->bind($req1);
        $route->setParameter('user', $member1);
        $req1->setRouteResolver(fn () => $route);

        $res1 = $middleware->handle($req1, fn () => new Response('OK'));
        $this->assertEquals(200, $res1->getStatusCode());

        // Member accessing other user -> 403
        $req2 = Request::create('/test', 'GET');
        $req2->setUserResolver(fn () => $member1);
        $route2 = new \Illuminate\Routing\Route('GET', '/test/{user}', []);
        $route2->bind($req2);
        $route2->setParameter('user', $member2);
        $req2->setRouteResolver(fn () => $route2);

        try {
            $middleware->handle($req2, fn () => new Response('OK'));
            $this->fail('Expected HttpException 403');
        } catch (HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }

        // Admin accessing other user -> allowed
        $req3 = Request::create('/test', 'GET');
        $req3->setUserResolver(fn () => $admin);
        $route3 = new \Illuminate\Routing\Route('GET', '/test/{user}', []);
        $route3->bind($req3);
        $route3->setParameter('user', $member2);
        $req3->setRouteResolver(fn () => $route3);

        $res3 = $middleware->handle($req3, fn () => new Response('OK'));
        $this->assertEquals(200, $res3->getStatusCode());
    }

    public function test_route_post_parameter_ownership(): void
    {
        $middleware = new EnsurePostOwnership();
        $member1 = User::factory()->member()->create();
        $member2 = User::factory()->member()->create();
        $admin = User::factory()->admin()->create();

        $post1 = Post::factory()->create(['user_id' => $member1->id]);

        // Member1 accessing own post GET
        $req1 = Request::create('/test', 'GET');
        $req1->setUserResolver(fn () => $member1);
        $route1 = new \Illuminate\Routing\Route('GET', '/test/{post}', []);
        $route1->bind($req1);
        $route1->setParameter('post', $post1);
        $req1->setRouteResolver(fn () => $route1);

        $res1 = $middleware->handle($req1, fn () => new Response('OK'));
        $this->assertEquals(200, $res1->getStatusCode());

        // Member2 accessing Member1's post GET -> 403
        $req2 = Request::create('/test', 'GET');
        $req2->setUserResolver(fn () => $member2);
        $route2 = new \Illuminate\Routing\Route('GET', '/test/{post}', []);
        $route2->bind($req2);
        $route2->setParameter('post', $post1);
        $req2->setRouteResolver(fn () => $route2);

        try {
            $middleware->handle($req2, fn () => new Response('OK'));
            $this->fail('Expected HttpException 403');
        } catch (HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }

        // Admin accessing Member1's post GET -> allowed
        $req3 = Request::create('/test', 'GET');
        $req3->setUserResolver(fn () => $admin);
        $route3 = new \Illuminate\Routing\Route('GET', '/test/{post}', []);
        $route3->bind($req3);
        $route3->setParameter('post', $post1);
        $req3->setRouteResolver(fn () => $route3);

        $res3 = $middleware->handle($req3, fn () => new Response('OK'));
        $this->assertEquals(200, $res3->getStatusCode());

        // Admin attempting to MODIFY Member1's post (PUT/DELETE) -> 403
        $req4 = Request::create('/test', 'DELETE');
        $req4->setUserResolver(fn () => $admin);
        $route4 = new \Illuminate\Routing\Route('DELETE', '/test/{post}', []);
        $route4->bind($req4);
        $route4->setParameter('post', $post1);
        $req4->setRouteResolver(fn () => $route4);

        try {
            $middleware->handle($req4, fn () => new Response('OK'));
            $this->fail('Expected HttpException 403');
        } catch (HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }
    }

    public function test_route_post_parameter_as_scalar_id_and_missing_post(): void
    {
        $middleware = new EnsurePostOwnership();
        $member1 = User::factory()->member()->create();
        $member2 = User::factory()->member()->create();
        $post1 = Post::factory()->create(['user_id' => $member1->id]);

        // Scalar id of an owned post -> allowed
        $req1 = Request::create('/test', 'GET');
        $req1->setUserResolver(fn () => $member1);
        $route1 = new \Illuminate\Routing\Route('GET', '/test/{post}', []);
        $route1->bind($req1);
        $route1->setParameter('post', $post1->id);
        $req1->setRouteResolver(fn () => $route1);
        $this->assertEquals(200, $middleware->handle($req1, fn () => new Response('OK'))->getStatusCode());

        // Scalar id of someone else's post -> 403
        $req2 = Request::create('/test', 'GET');
        $req2->setUserResolver(fn () => $member2);
        $route2 = new \Illuminate\Routing\Route('GET', '/test/{post}', []);
        $route2->bind($req2);
        $route2->setParameter('post', $post1->id);
        $req2->setRouteResolver(fn () => $route2);

        try {
            $middleware->handle($req2, fn () => new Response('OK'));
            $this->fail('Expected HttpException 403');
        } catch (HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }

        // Non-existing post id -> passes through (404 handled by route model binding)
        $req3 = Request::create('/test', 'GET');
        $req3->setUserResolver(fn () => $member1);
        $route3 = new \Illuminate\Routing\Route('GET', '/test/{post}', []);
        $route3->bind($req3);
        $route3->setParameter('post', 999999);
        $req3->setRouteResolver(fn () => $route3);
        $this->assertEquals(200, $middleware->handle($req3, fn () => new Response('OK'))->getStatusCode());
    }
}

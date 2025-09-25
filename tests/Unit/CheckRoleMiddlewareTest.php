<?php

namespace Tests\Unit;

use App\Http\Middleware\CheckRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class CheckRoleMiddlewareTest extends TestCase
{
    protected CheckRole $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CheckRole();
    }

    public function test_allows_user_with_correct_role()
    {
        $user = User::factory()->make(['role' => 'admin']);
        
        $request = Request::create('/test');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $this->middleware->handle($request, function () {
            return new Response('Success', 200);
        }, 'admin');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    public function test_allows_user_with_one_of_multiple_roles()
    {
        $user = User::factory()->make(['role' => 'manager']);
        
        $request = Request::create('/test');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $this->middleware->handle($request, function () {
            return new Response('Success', 200);
        }, 'admin', 'manager');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    public function test_denies_user_with_incorrect_role()
    {
        $user = User::factory()->make(['role' => 'user']);
        
        $request = Request::create('/test');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $this->middleware->handle($request, function () {
            return new Response('Success', 200);
        }, 'admin');

        $this->assertEquals(403, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Forbidden', $responseData['message']);
    }

    public function test_denies_unauthenticated_user()
    {
        $request = Request::create('/test');
        $request->setUserResolver(function () {
            return null;
        });

        $response = $this->middleware->handle($request, function () {
            return new Response('Success', 200);
        }, 'admin');

        $this->assertEquals(401, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Unauthorized', $responseData['message']);
    }
}
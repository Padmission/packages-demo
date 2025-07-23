<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\HandleMissingTenant;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class HandleMissingTenantTest extends TestCase
{
    use RefreshDatabase;

    protected HandleMissingTenant $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new HandleMissingTenant();
    }

    public function test_allows_request_without_tenant_parameter()
    {
        $request = Request::create('/admin/dashboard');
        $request->setRouteResolver(function () {
            $route = new Route('GET', '/admin/dashboard', []);
            return $route;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return response('success');
        });

        $this->assertEquals('success', $response->getContent());
    }

    public function test_allows_request_with_valid_tenant()
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $user->teams()->attach($team);

        $this->actingAs($user);

        $request = Request::create("/admin/{$team->id}/dashboard");
        $request->setRouteResolver(function () use ($team) {
            $route = new Route('GET', '/admin/{tenant}/dashboard', []);
            $route->bind($request = Request::create("/admin/{$team->id}/dashboard"));
            $route->setParameter('tenant', $team->id);
            return $route;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return response('success');
        });

        $this->assertEquals('success', $response->getContent());
    }

    public function test_redirects_demo_user_when_tenant_missing()
    {
        $demoUser = User::factory()->create([
            'email' => 'demo_test@demo.padmission.com',
        ]);

        $this->actingAs($demoUser);

        $request = Request::create('/admin/999/dashboard');
        $request->setRouteResolver(function () {
            $route = new Route('GET', '/admin/{tenant}/dashboard', []);
            $route->bind($request = Request::create('/admin/999/dashboard'));
            $route->setParameter('tenant', 999);
            return $route;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return response('should not reach here');
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('admin/login', $response->headers->get('Location'));
        $this->assertEquals('Your demo session has expired. Please login again to continue.', session('error'));
    }

    public function test_shows_404_for_non_demo_user_when_tenant_missing()
    {
        $user = User::factory()->create([
            'email' => 'regular@example.com',
        ]);

        $this->actingAs($user);

        $request = Request::create('/admin/999/dashboard');
        $request->setRouteResolver(function () {
            $route = new Route('GET', '/admin/{tenant}/dashboard', []);
            $route->bind($request = Request::create('/admin/999/dashboard'));
            $route->setParameter('tenant', 999);
            return $route;
        });

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Team not found.');

        $this->middleware->handle($request, function ($req) {
            return response('should not reach here');
        });
    }

    public function test_shows_403_when_user_cannot_access_tenant()
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();
        // User is not attached to this team

        $this->actingAs($user);

        $request = Request::create("/admin/{$team->id}/dashboard");
        $request->setRouteResolver(function () use ($team) {
            $route = new Route('GET', '/admin/{tenant}/dashboard', []);
            $route->bind($request = Request::create("/admin/{$team->id}/dashboard"));
            $route->setParameter('tenant', $team->id);
            return $route;
        });

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('You do not have access to this team.');

        $this->middleware->handle($request, function ($req) {
            return response('should not reach here');
        });
    }

    public function test_allows_guest_access_when_tenant_exists()
    {
        $team = Team::factory()->create();

        $request = Request::create("/admin/{$team->id}/dashboard");
        $request->setRouteResolver(function () use ($team) {
            $route = new Route('GET', '/admin/{tenant}/dashboard', []);
            $route->bind($request = Request::create("/admin/{$team->id}/dashboard"));
            $route->setParameter('tenant', $team->id);
            return $route;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return response('success');
        });

        $this->assertEquals('success', $response->getContent());
    }
}
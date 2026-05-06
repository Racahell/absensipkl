<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BlackBoxRouteCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_blocked_from_all_web_auth_routes(): void
    {
        $covered = 0;

        foreach (Route::getRoutes() as $route) {
            $middleware = $route->gatherMiddleware();
            if (! in_array('web', $middleware, true) || ! in_array('auth', $middleware, true)) {
                continue;
            }

            $uri = $this->materializeUri($route->uri());
            $method = $this->pickHttpMethod($route->methods());
            if ($method === null) {
                continue;
            }

            $response = $this->call($method, '/'.$uri);
            $covered++;

            $this->assertContains($response->getStatusCode(), [301, 302, 303, 307, 308], "Route [{$method} {$uri}] should redirect guest.");
            $location = (string) $response->headers->get('Location', '');
            $this->assertTrue(
                str_contains($location, '/login'),
                "Route [{$method} {$uri}] redirect should point to login. Actual: {$location}"
            );
        }

        $this->assertGreaterThan(0, $covered, 'No auth routes were covered.');
    }

    public function test_authenticated_superadmin_can_reach_non_parameterized_web_get_routes(): void
    {
        $superadmin = $this->makeUser('superadmin', [
            'username' => 'route_superadmin',
            'email' => 'route_superadmin@example.test',
            'password' => Hash::make('password123'),
        ]);

        $covered = 0;

        foreach (Route::getRoutes() as $route) {
            $methods = $route->methods();
            if (! in_array('GET', $methods, true) && ! in_array('HEAD', $methods, true)) {
                continue;
            }

            if (! in_array('web', $route->gatherMiddleware(), true)) {
                continue;
            }

            $uri = $route->uri();
            if ($this->shouldSkipForSuperadminReachability($uri)) {
                continue;
            }

            $response = $this->actingAs($superadmin)->get('/'.$uri);
            $covered++;

            // Route reachable means not an unhandled server failure.
            $this->assertNotContains(
                $response->getStatusCode(),
                [500, 502, 503],
                "Route [GET {$uri}] produced server error for superadmin."
            );
        }

        $this->assertGreaterThan(0, $covered, 'No GET web routes were covered for superadmin.');
    }

    private function pickHttpMethod(array $methods): ?string
    {
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $candidate) {
            if (in_array($candidate, $methods, true)) {
                return $candidate;
            }
        }

        return null;
    }

    private function materializeUri(string $uri): string
    {
        return preg_replace_callback('/\{[^}]+\}/', function (array $matches): string {
            $raw = trim($matches[0], '{}');
            $key = str_replace('?', '', $raw);

            return match ($key) {
                'hash' => str_repeat('a', 40),
                'token' => 'dummy-token',
                default => '1',
            };
        }, $uri) ?? $uri;
    }

    private function shouldSkipForSuperadminReachability(string $uri): bool
    {
        if (str_contains($uri, '{')) {
            return true;
        }

        $excludedPrefixes = [
            'storage/',
            'up',
            'login',
            'register',
            'forgot-password',
            'reset-password',
            'auth/google/callback',
            'email/verify/',
        ];

        foreach ($excludedPrefixes as $prefix) {
            if ($uri === $prefix || str_starts_with($uri, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function makeUser(string $role, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'name' => 'User '.strtoupper($role),
            'role' => $role,
            'nis' => (string) random_int(10000000, 99999999),
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'must_reset_password' => false,
            'must_change_password' => false,
            'is_deleted' => false,
            'department_name' => 'RPL',
            'class_name' => 'XII-RPL-1',
        ], $overrides));
    }
}


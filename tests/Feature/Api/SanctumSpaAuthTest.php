<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

class SanctumSpaAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_sanctum_spa_login_and_logout_flow(): void
    {
        $user = User::factory()->create();

        $cookies = $this->fetchCsrfCookies();

        $loginResponse = $this->withSpaHeaders()
            ->withCookies($cookies)
            ->withHeader('X-XSRF-TOKEN', $cookies['XSRF-TOKEN'])
            ->postJson('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $loginResponse->assertNoContent();
        $cookies = array_merge($cookies, $this->cookiesFromResponse($loginResponse));

        $this->withSpaHeaders()
            ->withCookies($cookies)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.plan', $user->plan)
            ->assertJsonPath('data.settings.user_id', $user->id);

        $cookies = array_merge($cookies, $this->fetchCsrfCookies($cookies));

        $logoutResponse = $this->withSpaHeaders()
            ->withCookies($cookies)
            ->withHeader('X-XSRF-TOKEN', $cookies['XSRF-TOKEN'])
            ->postJson('/logout');

        $logoutResponse->assertNoContent();

        $this->refreshApplication();

        $this->withSpaHeaders()
            ->getJson('/api/me')
            ->assertStatus(401);
    }

    private function withSpaHeaders(): self
    {
        return $this->withHeader('Origin', 'http://localhost:3000')
            ->withHeader('Referer', 'http://localhost:3000');
    }

    private function fetchCsrfCookies(array $cookies = []): array
    {
        $response = $this->withCookies($cookies)->get('/sanctum/csrf-cookie');

        return array_merge($cookies, $this->cookiesFromResponse($response));
    }

    private function cookiesFromResponse($response): array
    {
        $cookies = [];

        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie instanceof Cookie) {
                $cookies[$cookie->getName()] = $cookie->getValue();
            }
        }

        return $cookies;
    }
}

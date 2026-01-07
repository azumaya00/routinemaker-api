<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MeTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/me')->assertStatus(401);
    }

    public function test_me_returns_user_plan_and_settings(): void
    {
        $user = User::factory()->create(['plan' => 'free']);
        $settings = UserSetting::factory()->for($user)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/me');

        $response->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.plan', 'free')
            ->assertJsonPath('data.settings.id', $settings->id);
    }
}

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

    /**
     * チュートリアル表示判定のテスト
     * tutorial_dismissed_at が null の場合は tutorial_should_show が true
     */
    public function test_me_returns_tutorial_should_show_true_when_dismissed_at_is_null(): void
    {
        $user = User::factory()->create(['tutorial_dismissed_at' => null]);
        UserSetting::factory()->for($user)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/me');

        $response->assertOk()
            ->assertJsonPath('data.user.tutorial_dismissed_at', null)
            ->assertJsonPath('data.user.tutorial_should_show', true);
    }

    /**
     * チュートリアル非表示判定のテスト
     * tutorial_dismissed_at が設定されている場合は tutorial_should_show が false
     */
    public function test_me_returns_tutorial_should_show_false_when_dismissed_at_is_set(): void
    {
        $user = User::factory()->create(['tutorial_dismissed_at' => now()]);
        UserSetting::factory()->for($user)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/me');

        $response->assertOk()
            ->assertJsonPath('data.user.tutorial_should_show', false)
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'tutorial_dismissed_at',
                    ],
                ],
            ]);
    }
}

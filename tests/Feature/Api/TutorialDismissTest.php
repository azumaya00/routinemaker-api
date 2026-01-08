<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TutorialDismissTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 認証が必要なエンドポイントのテスト
     */
    public function test_tutorial_dismiss_requires_authentication(): void
    {
        $this->postJson('/api/tutorial/dismiss')->assertStatus(401);
    }

    /**
     * チュートリアルを非表示にするテスト
     * tutorial_dismissed_at が null の場合、現在時刻がセットされる
     */
    public function test_tutorial_dismiss_sets_dismissed_at_when_null(): void
    {
        $user = User::factory()->create(['tutorial_dismissed_at' => null]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/tutorial/dismiss');

        $response->assertOk()
            ->assertJsonPath('data.tutorial_should_show', false)
            ->assertJsonStructure([
                'data' => [
                    'tutorial_dismissed_at',
                    'tutorial_should_show',
                ],
            ]);

        // DB に保存されていることを確認
        $user->refresh();
        $this->assertNotNull($user->tutorial_dismissed_at);
    }

    /**
     * チュートリアル非表示APIの冪等性テスト
     * 既に tutorial_dismissed_at が設定されている場合、値は変更されない
     */
    public function test_tutorial_dismiss_is_idempotent(): void
    {
        $dismissedAt = now()->subDay();
        $user = User::factory()->create(['tutorial_dismissed_at' => $dismissedAt]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/tutorial/dismiss');

        $response->assertOk()
            ->assertJsonPath('data.tutorial_should_show', false);

        // DB の値が変更されていないことを確認
        $user->refresh();
        $this->assertEquals($dismissedAt->format('Y-m-d H:i:s'), $user->tutorial_dismissed_at->format('Y-m-d H:i:s'));
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    /**
     * アカウント削除の正常系テスト
     * パスワードが一致する場合、論理削除が実行され、退会後に /api/me が 401 になる
     */
    public function test_delete_account_success_with_correct_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        Sanctum::actingAs($user);

        // アカウント削除を実行
        $response = $this->deleteJson('/api/me', [
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'アカウントを削除しました。');

        // 論理削除されていることを確認
        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);

        // 退会後に /api/me が 401 になることを確認
        $this->getJson('/api/me')->assertStatus(401);
    }

    /**
     * アカウント削除の異常系テスト
     * パスワードが不一致の場合、422 を返す
     */
    public function test_delete_account_fails_with_incorrect_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        Sanctum::actingAs($user);

        // 間違ったパスワードでアカウント削除を試行
        $response = $this->deleteJson('/api/me', [
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'パスワードが一致しません。');

        // ユーザーが削除されていないことを確認
        $this->assertNotSoftDeleted('users', [
            'id' => $user->id,
        ]);
    }
}

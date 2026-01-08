<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * 管理者機能（is_admin）のFeatureテスト
 * 
 * 目的：
 * - 新規登録時にis_adminが必ずfalseになることを保証
 * - 管理者ミドルウェアが正しく動作することを保証
 * - 一般ユーザーと管理者ユーザーで適切にアクセス制御されることを保証
 */
class AdminTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テスト1: 新規登録時にis_adminが必ずfalseになること
     * 
     * 仕様：
     * - 通常の登録リクエストでis_adminがfalseになること
     * - 外部入力でis_admin=trueを送ってもtrueにならないこと（セキュリティ対策）
     */
    public function test_registration_always_sets_is_admin_to_false(): void
    {
        // 通常の登録リクエスト（is_adminを送らない）
        $response = $this->postJson('/register', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertNoContent();

        // 作成されたユーザーのis_adminがfalseであることを確認
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->is_admin, '新規登録時はis_adminがfalseである必要があります');
    }

    /**
     * テスト1の追加: 外部入力でis_admin=trueを送ってもfalseのままであること
     * 
     * セキュリティ対策：
     * - 外部からis_admin=trueを送っても、登録時にtrueにならないことを確認
     * - RegisteredUserControllerで明示的にfalseを設定しているため、外部入力は無視される
     */
    public function test_registration_ignores_external_is_admin_input(): void
    {
        // is_admin=trueを外部入力として送る（悪意のあるリクエストをシミュレート）
        $response = $this->postJson('/register', [
            'email' => 'hacker@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_admin' => true, // 外部入力でis_admin=trueを送る
        ]);

        $response->assertNoContent();

        // 作成されたユーザーのis_adminがfalseであることを確認（外部入力が無視されている）
        $user = User::where('email', 'hacker@example.com')->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->is_admin, '外部入力でis_admin=trueを送ってもfalseのままである必要があります');
    }

    /**
     * テスト2: 一般ユーザーで/api/admin/pingにアクセスすると403になること
     * 
     * 仕様：
     * - is_admin=falseのユーザーでログイン状態にする
     * - /api/admin/pingにアクセス
     * - ステータスが403であること
     * - レスポンスボディにエラーメッセージが含まれること
     */
    public function test_non_admin_user_cannot_access_admin_endpoint(): void
    {
        // is_admin=falseの一般ユーザーを作成
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        // 認証済みユーザーとしてリクエスト
        Sanctum::actingAs($user);

        // 管理者専用エンドポイントにアクセス
        $response = $this->getJson('/api/admin/ping');

        // 403 Forbiddenが返されることを確認
        $response->assertStatus(403);

        // エラーメッセージが含まれることを確認（既存のエラーレスポンス形式に合わせる）
        $response->assertJson([
            'message' => '管理者権限が必要です。',
        ]);
    }

    /**
     * テスト3: 管理者ユーザーで/api/admin/pingにアクセスすると200になること
     * 
     * 仕様：
     * - is_admin=trueのユーザーでログイン状態にする
     * - /api/admin/pingにアクセス
     * - ステータスが200であること
     * - レスポンスJSONに管理者情報が含まれること
     */
    public function test_admin_user_can_access_admin_endpoint(): void
    {
        // is_admin=trueの管理者ユーザーを作成
        $user = User::factory()->create([
            'is_admin' => true,
        ]);

        // 認証済みユーザーとしてリクエスト
        Sanctum::actingAs($user);

        // 管理者専用エンドポイントにアクセス
        $response = $this->getJson('/api/admin/ping');

        // 200 OKが返されることを確認
        $response->assertStatus(200);

        // レスポンスJSONの構造を確認
        $response->assertJsonStructure([
            'message',
            'user' => [
                'id',
                'email',
                'is_admin',
            ],
        ]);

        // レスポンス内容を確認
        $response->assertJson([
            'message' => 'Admin access granted',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'is_admin' => true,
            ],
        ]);
    }
}

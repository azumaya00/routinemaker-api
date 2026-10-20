<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleted_email_cannot_register(): void
    {
        $user = User::factory()->create();
        $password = $user->password;
        $user->delete();

        $this->postJson('/register', [
            'email' => $user->email,
            'password' => 'newPassword123',
            'password_confirmation' => 'newPassword123',
        ])->assertStatus(422)->assertJsonPath('message', 'このメールアドレスは退会済みです。パスワード再設定から復元してください。');

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertSame($password, $user->fresh()->password);
    }

    public function test_deleted_user_receives_reset_link_without_revealing_unknown_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $user->delete();

        $message = 'パスワードリセットリンクを送信しました。メールをご確認ください。';
        $this->postJson('/api/forgot-password', ['email' => $user->email])
            ->assertOk()->assertJsonPath('message', $message);
        Notification::assertSentTo($user, ResetPasswordNotification::class);
        $this->postJson('/api/forgot-password', ['email' => 'missing@example.com'])
            ->assertOk()->assertJsonPath('message', $message);
    }

    public function test_deleted_user_can_reset_password_and_log_in(): void
    {
        $user = User::factory()->create(['plan' => 'unlimited', 'is_admin' => true]);
        $user->delete();
        $token = Password::broker()->getRepository()->create($user);

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'newPassword123',
            'password_confirmation' => 'newPassword123',
        ])->assertOk();

        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
        $this->assertSame('unlimited', $user->fresh()->plan);
        $this->assertTrue($user->fresh()->is_admin);
        $this->postJson('/login', ['email' => $user->email, 'password' => 'newPassword123'])->assertNoContent();
    }

    public function test_invalid_reset_token_does_not_restore_deleted_user(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => 'invalid',
            'password' => 'newPassword123',
            'password_confirmation' => 'newPassword123',
        ])->assertStatus(422);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_reset_revokes_personal_access_tokens(): void
    {
        $user = User::factory()->create();
        $oldRememberToken = $user->remember_token;
        $user->createToken('test');
        $token = Password::broker()->getRepository()->create($user);

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'newPassword123',
            'password_confirmation' => 'newPassword123',
        ])->assertOk();

        $this->assertCount(0, $user->tokens);
        $this->assertTrue(Hash::check('newPassword123', $user->fresh()->password));
        $this->assertNotSame($oldRememberToken, $user->fresh()->remember_token);
    }

    public function test_reset_deletes_database_sessions_for_user(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create();
        $other = User::factory()->create();
        DB::table('sessions')->insert([
            ['id' => 'user-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()],
            ['id' => 'other-session', 'user_id' => $other->id, 'payload' => '', 'last_activity' => time()],
        ]);
        $token = Password::broker()->getRepository()->create($user);

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'newPassword123',
            'password_confirmation' => 'newPassword123',
        ])->assertOk();

        $this->assertDatabaseMissing('sessions', ['id' => 'user-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'other-session']);
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/login', ['email' => 'missing@example.com', 'password' => 'wrong'])->assertStatus(422);
        }

        $this->postJson('/login', ['email' => 'missing@example.com', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_google_email_match_does_not_restore_deleted_user_with_different_provider_id(): void
    {
        $user = User::factory()->create(['provider' => 'google', 'provider_id' => 'old-id']);
        $user->delete();
        $this->mockGoogleUser($user->email, 'new-id');

        $this->getJson('/api/auth/google/callback')->assertStatus(422)
            ->assertJsonPath('message', 'このメールアドレスは退会済みです。パスワード再設定から復元してください。');
        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertSame('old-id', $user->fresh()->provider_id);
    }

    public function test_google_provider_id_match_restores_deleted_user(): void
    {
        $user = User::factory()->create(['provider' => 'google', 'provider_id' => 'same-id']);
        $user->delete();
        $this->mockGoogleUser($user->email, 'same-id');

        $this->getJson('/api/auth/google/callback')->assertNoContent();
        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
    }

    private function mockGoogleUser(string $email, string $id): void
    {
        $googleUser = new SocialiteUser;
        $googleUser->map(['email' => $email, 'id' => $id, 'name' => 'Test User']);
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn(
            \Mockery::mock()->shouldReceive('user')->once()->andReturn($googleUser)->getMock()
        );
    }
}

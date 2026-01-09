<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Google の認可画面へリダイレクトする。
     */
    public function redirect(): RedirectResponse
    {
        // SPA からのリクエストでセッション state を使わないため stateless を指定
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Google からのコールバックを受け取り、ユーザーを作成/復元してログインさせる。
     */
    public function callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Google認証に失敗しました。もう一度お試しください。',
            ], 400);
        }

        $email = $googleUser->getEmail();
        if (! $email) {
            return response()->json([
                'message' => 'Googleアカウントにメールアドレスがありません。別のアカウントをお試しください。',
            ], 422);
        }

        $googleId = $googleUser->getId();
        $googleName = $googleUser->getName() ?: strtok($email, '@') ?: 'User';

        // A: provider + provider_id で復元を優先
        $user = User::withTrashed()
            ->where('provider', 'google')
            ->where('provider_id', $googleId)
            ->first();

        if ($user) {
            $restored = false;
            if ($user->trashed()) {
                $user->restore();
                $restored = true;
            }
            // 退会復活時は初期プランに戻す
            $user->plan = 'free';
            // ずれがあれば現在のGoogle情報で更新
            $user->provider = 'google';
            $user->provider_id = $googleId;
            $user->email = $email;
            $user->name = $googleName;
            $user->save();

            if ($restored) {
                // 復元発生時のみログを出す（本番でも邪魔にならない情報レベル）
                Log::info('Google login restore by provider_id', [
                    'user_id' => $user->id,
                    'provider_id' => $googleId,
                ]);
            }
        } else {
            // B: email で withTrashed を確認
            $userByEmail = User::withTrashed()->where('email', $email)->first();
            if ($userByEmail) {
                if (! $userByEmail->trashed()) {
                    // 生存中の別アカウントと衝突する場合は拒否
                    return response()->json([
                        'message' => 'このメールアドレスは既に別のアカウントで利用されています。',
                    ], 422);
                }

                // 退会済みで provider=google の場合のみ復元を許可
                if ($userByEmail->provider === 'google') {
                    $userByEmail->restore();
                    $userByEmail->plan = 'free'; // 初期プランに戻す
                    $userByEmail->provider = 'google';
                    $userByEmail->provider_id = $googleId; // GoogleのIDで上書き
                    $userByEmail->name = $googleName;
                    $userByEmail->save();

                    Log::info('Google login restore by email', [
                        'user_id' => $userByEmail->id,
                        'provider_id' => $googleId,
                    ]);

                    $user = $userByEmail;
                } else {
                    // Google以外の方法で退会したメールは復活させない
                    return response()->json([
                        'message' => 'このメールアドレスは別の認証方法で登録されています。',
                    ], 422);
                }
            } else {
                // C: 該当なしの場合は新規作成
                $user = User::create([
                    'name' => $googleName,
                    'email' => $email,
                    'password' => null,
                    'plan' => 'free', // 既存仕様と同じ初期プラン
                    'provider' => 'google',
                    'provider_id' => $googleId,
                    // is_admin は fillable ではないため、デフォルト false が使われる
                ]);
            }
        }

        // 既存のメール/パスワードと同じ挙動でセッションを確立する。
        Auth::login($user);
        $request->session()->regenerate();

        // ブラウザ遷移時はフロントエンドへ返す。XHR など JSON 期待時は204で返す。
        if ($request->expectsJson()) {
            return response()->noContent();
        }

        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        // Google 認証成功後はアプリのホームではなく /routines に遷移させる
        return redirect()->away("{$frontendUrl}/routines");
    }
}

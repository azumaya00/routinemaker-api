<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $user = User::withTrashed()->where('email', $email)->first();

        if ($user) {
            // 論理削除済みなら復元する。
            if ($user->trashed()) {
                $user->restore();
            }

            // 名前が未設定の場合のみ Google の名前を採用する。
            if (! $user->name) {
                $user->name = $googleUser->getName() ?: strtok($email, '@') ?: 'User';
            }

            // provider 情報が未設定の場合のみ保存する（既存設定は尊重）。
            if (! $user->provider) {
                $user->provider = 'google';
            }
            if (! $user->provider_id) {
                $user->provider_id = $googleUser->getId();
            }

            $user->save();
        } else {
            // 新規ユーザーを作成する。パスワードは Google 認証のみで扱うため null。
            $user = User::create([
                'name' => $googleUser->getName() ?: strtok($email, '@') ?: 'User',
                'email' => $email,
                'password' => null,
                'plan' => 'free', // 既存仕様と同じ初期プラン
                'provider' => 'google',
                'provider_id' => $googleUser->getId(),
                // is_admin は fillable ではないため、デフォルト false が使われる
            ]);
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

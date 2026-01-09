<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MeController extends Controller
{
    public function __invoke(Request $request)
    {
        // ログイン中ユーザーと設定をまとめて返す
        $user = $request->user();
        // 初回アクセス時はデフォルト設定を作成して返す
        $settings = $user->setting()->firstOrCreate([], [
            'theme' => 'light',
            'dark_mode' => 'system',
            'show_remaining_tasks' => false,
            'show_elapsed_time' => false,
            'show_celebration' => false,
        ]);

        // チュートリアル表示判定: tutorial_dismissed_at が NULL なら表示対象
        $tutorialShouldShow = $user->tutorial_dismissed_at === null;

        return response()->json([
            'data' => [
                'user' => array_merge(
                    $user->only(['id', 'name', 'email', 'plan', 'is_admin']),
                    [
                        // フロント側で退会UIを出し分けるための情報
                        'has_password' => $user->password !== null,
                        'provider' => $user->provider,
                        'tutorial_dismissed_at' => $user->tutorial_dismissed_at?->toIso8601String(),
                        'tutorial_should_show' => $tutorialShouldShow,
                    ]
                ),
                'plan' => $user->plan,
                'settings' => $settings,
            ],
        ]);
    }

    /**
     * アカウント削除（退会）
     * 
     * パスワード再入力必須で論理削除を実行
     */
    public function destroy(Request $request)
    {
        $user = $request->user();

        $expectsPassword = $user->password !== null;

        if ($expectsPassword) {
            // パスワードを持つユーザーは従来どおり再入力必須
            $validated = $request->validate([
                'password' => ['required', 'string'],
            ], [
                'password.required' => 'パスワードは必須です。',
            ]);

            // パスワードが一致しない場合は422を返す（従来仕様を維持）
            if (!Hash::check($validated['password'], $user->password)) {
                return response()->json([
                    'message' => 'パスワードが一致しません。',
                    'errors' => ['password' => ['パスワードが一致しません。']],
                ], 422);
            }
        } else {
            // ソーシャルログイン（password null）は直近ログイン済みをもって本人確認とする
            $request->validate([
                'password' => ['nullable', 'string'],
            ]);
            // password があっても無視して通す（再認証導線は作らない）
        }

        // 論理削除を実行（SoftDeletes）
        $user->delete();

        // ログアウト処理
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // セッション cookie の名称差分に備えて、複数パターンを明示的に削除する。
        $sessionCookie = config('session.cookie');
        return response()
            ->noContent()
            ->withCookie(cookie()->forget($sessionCookie))
            ->withCookie(cookie()->forget('laravel_session'))
            ->withCookie(cookie()->forget('laravel-session'))
            ->withCookie(cookie()->forget('XSRF-TOKEN'));
    }
}

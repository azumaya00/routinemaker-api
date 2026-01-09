<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function store(Request $request)
    {
        // SPA からの POST /login で 302 が返ると CORS で詰まるため、認証済みは 204 で終了する。
        if (Auth::check()) {
            return response()->noContent();
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // SoftDeletes を使用しているため、削除されたユーザーは自動的に除外される
        // ただし、明示的にチェックしてより明確なエラーメッセージを返す
        if (! Auth::attempt($credentials)) {
            // web ルートでも JSON を返してリダイレクトを発生させない。
            return response()->json([
                'message' => 'メールアドレスまたはパスワードが正しくありません。',
            ], 422);
        }

        $request->session()->regenerate();

        return response()->noContent();
    }

    public function destroy(Request $request)
    {
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

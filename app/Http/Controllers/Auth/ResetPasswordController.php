<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;

class ResetPasswordController extends Controller
{
    /**
     * パスワードリセット実行
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(8)->letters()->numbers(),
            ],
        ], [
            'token.required' => 'トークンは必須です。',
            'email.required' => 'メールアドレスは必須です。',
            'email.email' => '有効なメールアドレスを入力してください。',
            'password.required' => 'パスワードは必須です。',
            'password.confirmed' => 'パスワードが一致しません。',
            'password.min' => 'パスワードは8文字以上である必要があります。',
            'password.letters' => 'パスワードには英字が含まれている必要があります。',
            'password.numbers' => 'パスワードには数字が含まれている必要があります。',
        ]);

        // パスワードリセットを実行
        $status = Password::reset(
            $validated,
            function ($user, $password) {
                $user->password = $password;
                $user->save();
            }
        );

        // リセット成功時
        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'パスワードをリセットしました。',
            ]);
        }

        // リセット失敗時（トークン無効など）
        return response()->json([
            'message' => 'パスワードリセットに失敗しました。リンクが無効または期限切れの可能性があります。',
        ], 422);
    }
}

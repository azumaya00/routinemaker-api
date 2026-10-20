<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Support\Str;

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

        $user = User::where('email', $validated['email'])->first();
        if ($user) {
            // 生存ユーザーは通常の broker で検証する
            $status = Password::reset(
                $request->only(
                    'email',
                    'password',
                    'password_confirmation',
                    'token'
                ),
                function ($user, $password) {
                    $user->password = $password;
                    $user->save();
                }
            );
        } else {
            $user = User::withTrashed()->onlyTrashed()->where('email', $validated['email'])->first();
            $tokens = Password::broker()->getRepository();
            $status = Password::INVALID_TOKEN;
            if ($user && $tokens->exists($user, $validated['token'])) {
                $user->restore();
                $user->password = $validated['password'];
                $user->save();
                $tokens->delete($user);
                $status = Password::PASSWORD_RESET;
            }
        }

        // リセット成功時
        if ($status === Password::PASSWORD_RESET) {
            $user->remember_token = Str::random(60);
            $user->save();
            $user->tokens()->delete();
            if (config('session.driver') === 'database') {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
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

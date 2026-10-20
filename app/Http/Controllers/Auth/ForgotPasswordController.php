<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    /**
     * パスワードリセットリンク送信
     * 
     * セキュリティのため、存在しないメールアドレスでも常に200を返す
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'メールアドレスは必須です。',
            'email.email' => '有効なメールアドレスを入力してください。',
        ]);

        // 生存ユーザーは通常の broker から送信する
        if (User::where('email', $validated['email'])->exists()) {
            Password::sendResetLink(['email' => $validated['email']]);
        } else {
            $user = User::withTrashed()->onlyTrashed()->where('email', $validated['email'])->first();
            if ($user) {
                $tokens = Password::broker()->getRepository();
                if (! $tokens->recentlyCreatedToken($user)) {
                    $user->sendPasswordResetNotification($tokens->create($user));
                }
            }
        }

        // 常に200を返す（アカウント存在の情報漏洩を防ぐ）
        return response()->json([
            'message' => 'パスワードリセットリンクを送信しました。メールをご確認ください。',
        ]);
    }
}

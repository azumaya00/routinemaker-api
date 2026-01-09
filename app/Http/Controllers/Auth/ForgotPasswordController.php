<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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

        // パスワードリセットリンクを送信
        // セキュリティのため、存在しないメールでも常に成功レスポンスを返す
        Password::sendResetLink(['email' => $validated['email']]);

        // 常に200を返す（アカウント存在の情報漏洩を防ぐ）
        return response()->json([
            'message' => 'パスワードリセットリンクを送信しました。メールをご確認ください。',
        ]);
    }
}

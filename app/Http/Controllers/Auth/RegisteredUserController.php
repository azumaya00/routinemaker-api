<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class RegisteredUserController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            // 最小の強度として「8文字以上 + 英字/数字を各1文字以上」を要求する。
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->letters()->numbers(),
            ],
        ], [
            // カスタムエラーメッセージ（日本語）
            'email.required' => 'メールアドレスは必須です。',
            'email.email' => '有効なメールアドレスを入力してください。',
            'password.required' => 'パスワードは必須です。',
            'password.confirmed' => 'パスワードが一致しません。',
            'password.min' => 'パスワードは8文字以上である必要があります。',
            'password.letters' => 'パスワードには英字が含まれている必要があります。',
            'password.numbers' => 'パスワードには数字が含まれている必要があります。',
        ]);

        // 削除されていないユーザーが既に存在するかチェック
        $existingUser = User::where('email', $validated['email'])->first();
        if ($existingUser) {
            return response()->json([
                'message' => 'このメールアドレスは既に登録されています。',
            ], 422);
        }

        // 削除されたユーザーが存在するかチェック（論理削除されたユーザーも含めて検索）
        $deletedUser = User::withTrashed()->where('email', $validated['email'])->first();
        
        if ($deletedUser) {
            // 削除されたユーザーを復活させる
            $deletedUser->restore();
            
            // パスワードを新しいものに更新
            $deletedUser->password = $validated['password'];
            $deletedUser->save();
            
            $user = $deletedUser;
        } else {
            // 新規ユーザーを作成
            // 登録時点ではプロフィール入力をしない前提のため、名前は仮で用意する。
            // 新規登録時は必ず is_admin=false を保証（外部入力から変更できないようにする）
            $user = User::create([
                'name' => strtok($validated['email'], '@') ?: 'User',
                'email' => $validated['email'],
                'password' => $validated['password'],
                'is_admin' => false, // 新規登録時は必ずfalse（念のため明示的に設定）
            ]);
        }

        // SPA の登録直後にログイン済みで扱えるよう、セッションを作成する。
        Auth::login($user);
        $request->session()->regenerate();

        return response()->noContent();
    }
}

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
            'email' => ['required', 'email', 'unique:users,email'],
            // 最小の強度として「8文字以上 + 英字/数字を各1文字以上」を要求する。
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->letters()->numbers(),
            ],
        ]);

        // 登録時点ではプロフィール入力をしない前提のため、名前は仮で用意する。
        $user = User::create([
            'name' => strtok($validated['email'], '@') ?: 'User',
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        // SPA の登録直後にログイン済みで扱えるよう、セッションを作成する。
        Auth::login($user);
        $request->session()->regenerate();

        return response()->noContent();
    }
}

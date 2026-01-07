<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
            'enable_task_estimated_time' => false,
            'show_celebration' => false,
        ]);

        return response()->json([
            'data' => [
                'user' => $user->only(['id', 'name', 'email', 'plan']),
                'plan' => $user->plan,
                'settings' => $settings,
            ],
        ]);
    }
}

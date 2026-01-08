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
            'show_celebration' => false,
        ]);

        // チュートリアル表示判定: tutorial_dismissed_at が NULL なら表示対象
        $tutorialShouldShow = $user->tutorial_dismissed_at === null;

        return response()->json([
            'data' => [
                'user' => array_merge(
                    $user->only(['id', 'name', 'email', 'plan', 'is_admin']),
                    [
                        'tutorial_dismissed_at' => $user->tutorial_dismissed_at?->toIso8601String(),
                        'tutorial_should_show' => $tutorialShouldShow,
                    ]
                ),
                'plan' => $user->plan,
                'settings' => $settings,
            ],
        ]);
    }
}

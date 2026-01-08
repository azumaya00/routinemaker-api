<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TutorialDismissController extends Controller
{
    /**
     * チュートリアルを非表示にする
     * tutorial_dismissed_at が NULL の場合のみ現在時刻をセット（冪等性を保つ）
     */
    public function __invoke(Request $request)
    {
        $user = $request->user();

        // tutorial_dismissed_at が NULL の場合のみ更新
        if ($user->tutorial_dismissed_at === null) {
            $user->tutorial_dismissed_at = now();
            $user->save();
        }

        // 更新後の状態を返す
        return response()->json([
            'data' => [
                'tutorial_dismissed_at' => $user->tutorial_dismissed_at->toIso8601String(),
                'tutorial_should_show' => false,
            ],
        ]);
    }
}

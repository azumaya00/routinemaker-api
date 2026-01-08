<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * 管理者専用APIコントローラー
 * 
 * 動作確認用の最小限のエンドポイントを提供
 * 本番環境で公開したくない場合は、.env で無効化することを推奨
 */
class AdminController extends Controller
{
    /**
     * 管理者専用のpingエンドポイント（動作確認用）
     * 
     * このエンドポイントは管理者のみアクセス可能
     * 正常にアクセスできた場合は、ユーザー情報を返す
     */
    public function ping(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'message' => 'Admin access granted',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
            ],
        ]);
    }
}

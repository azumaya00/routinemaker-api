<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserSettingController extends Controller
{
    public function show(Request $request)
    {
        // 設定の取得（未作成ならデフォルト作成）
        $settings = $this->getOrCreateSettings($request);

        return response()->json([
            'data' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        // 設定の更新（UI制約はフロント側で担保）
        $settings = $this->getOrCreateSettings($request);

        $validated = $request->validate([
            'theme' => ['sometimes', 'in:light,soft,dark'],
            'dark_mode' => ['sometimes', 'in:off,on,system'],
            'show_remaining_tasks' => ['sometimes', 'boolean'],
            'show_elapsed_time' => ['sometimes', 'boolean'],
            'enable_task_estimated_time' => ['sometimes', 'boolean'],
            'show_celebration' => ['sometimes', 'boolean'],
        ]);

        $settings->fill($validated);
        $settings->save();

        return response()->json([
            'data' => $settings,
        ]);
    }

    private function getOrCreateSettings(Request $request)
    {
        // 初回アクセス時にデフォルトを作成
        return $request->user()->setting()->firstOrCreate([], [
            'theme' => 'light',
            'dark_mode' => 'system',
            'show_remaining_tasks' => false,
            'show_elapsed_time' => false,
            'enable_task_estimated_time' => false,
            'show_celebration' => false,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\History;
use App\Models\Routine;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function start(Request $request, int $id)
    {
        // 指定ルーティンのスナップショットで履歴を作成
        $routine = Routine::where('user_id', $request->user()->id)->findOrFail($id);

        $history = History::create([
            'user_id' => $request->user()->id,
            'routine_id' => $routine->id,
            'title' => $routine->title,
            'tasks' => $routine->tasks,
            'started_at' => now(),
            'completed' => false,
        ]);

        return response()->json([
            'data' => $history,
        ], 201);
    }

    public function complete(Request $request, int $id)
    {
        // 完了として終了時刻を記録
        $history = $this->findHistory($request, $id);
        $history->update([
            'finished_at' => now(),
            'completed' => true,
        ]);

        return response()->json([
            'data' => $history,
        ]);
    }

    public function abort(Request $request, int $id)
    {
        // 中断として終了時刻を記録
        $history = $this->findHistory($request, $id);
        $history->update([
            'finished_at' => now(),
            'completed' => false,
        ]);

        return response()->json([
            'data' => $history,
        ]);
    }

    private function findHistory(Request $request, int $id): History
    {
        // 他ユーザーの履歴は取得不可
        return History::where('user_id', $request->user()->id)->findOrFail($id);
    }
}

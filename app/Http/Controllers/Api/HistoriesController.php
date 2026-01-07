<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\History;
use Illuminate\Http\Request;

class HistoriesController extends Controller
{
    public function index(Request $request)
    {
        // 一覧は軽量にし、履歴の存在だけ分かる最小項目に絞る。
        $histories = History::where('user_id', $request->user()->id)
            ->orderByDesc('started_at')
            ->simplePaginate(20, [
                'id',
                'routine_id',
                'title',
                'started_at',
                'finished_at',
                'completed',
            ]);

        return response()->json([
            'data' => $histories->items(),
            'meta' => [
                'next_page_url' => $histories->nextPageUrl(),
                'prev_page_url' => $histories->previousPageUrl(),
            ],
        ]);
    }

    public function show(Request $request, History $history)
    {
        // 他ユーザーの履歴は存在を悟らせないため 404 で落とす。
        if ($history->user_id !== $request->user()->id) {
            abort(404);
        }

        // 詳細はスナップショットを含めて返す前提。
        return response()->json([
            'data' => [
                'id' => $history->id,
                'routine_id' => $history->routine_id,
                'title' => $history->title,
                'tasks' => $history->tasks,
                'started_at' => $history->started_at,
                'finished_at' => $history->finished_at,
                'completed' => $history->completed,
                'created_at' => $history->created_at,
                'updated_at' => $history->updated_at,
            ],
        ]);
    }
}

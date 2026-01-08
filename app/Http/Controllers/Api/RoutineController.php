<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Routine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoutineController extends Controller
{
    public function index(Request $request)
    {
        // 自分のルーティン一覧のみ取得
        $routines = Routine::where('user_id', $request->user()->id)
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $routines,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // freeユーザーの場合、既存のルーティン数をチェック（10件制限）
        if ($user->plan === 'free') {
            $existingCount = Routine::where('user_id', $user->id)->count();
            if ($existingCount >= 10) {
                return response()->json([
                    'message' => '無料プランではタスクリストは10件までです。',
                ], 422);
            }
        }

        // ルーティン作成（プラン制限もここで検証）
        $validated = $this->validateRoutine($request, false);

        $routine = Routine::create([
            'user_id' => $user->id,
            'title' => $validated['title'],
            'tasks' => $validated['tasks'],
        ]);

        return response()->json([
            'data' => $routine,
        ], 201);
    }

    public function show(Request $request, int $id)
    {
        // 自分のルーティンのみ参照可能
        $routine = $this->findRoutine($request, $id);

        return response()->json([
            'data' => $routine,
        ]);
    }

    public function update(Request $request, int $id)
    {
        // 更新対象は自分のルーティンのみ
        $routine = $this->findRoutine($request, $id);
        // 更新時は部分更新を許可
        $validated = $this->validateRoutine($request, true);

        $routine->fill($validated);
        $routine->save();

        return response()->json([
            'data' => $routine,
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        // 自分のルーティンのみ削除
        $routine = $this->findRoutine($request, $id);
        $routine->delete();

        return response()->json([
            'data' => [
                'deleted' => true,
            ],
        ]);
    }

    private function validateRoutine(Request $request, bool $isUpdate): array
    {
        // tasks は文字列配列として受け取る
        $rules = [
            'title' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'tasks' => [$isUpdate ? 'sometimes' : 'required', 'array'],
            'tasks.*' => ['string'],
        ];

        $validator = Validator::make($request->all(), $rules);
        $validator->after(function ($validator) use ($request) {
            if (! $request->has('tasks')) {
                return;
            }

            $tasks = $request->input('tasks', []);
            $plan = $request->user()->plan;

            // free/future_pro は 10 件上限、unlimited のみ解除
            if ($plan !== 'unlimited' && count($tasks) > 10) {
                $validator->errors()->add('tasks', 'Tasks must not exceed 10 for this plan.');
            }
        });

        return $validator->validate();
    }

    private function findRoutine(Request $request, int $id): Routine
    {
        // 他ユーザーのルーティンは取得不可
        return Routine::where('user_id', $request->user()->id)->findOrFail($id);
    }
}

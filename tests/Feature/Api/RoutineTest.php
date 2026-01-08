<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoutineTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_plan_rejects_more_than_ten_tasks(): void
    {
        $user = User::factory()->create(['plan' => 'free']);
        Sanctum::actingAs($user);

        $payload = [
            'title' => 'Test Routine',
            'tasks' => array_fill(0, 11, 'Task'),
        ];

        $this->postJson('/api/routines', $payload)->assertStatus(422);
    }

    public function test_unlimited_plan_allows_more_than_ten_tasks(): void
    {
        $user = User::factory()->create(['plan' => 'unlimited']);
        Sanctum::actingAs($user);

        $payload = [
            'title' => 'Test Routine',
            'tasks' => array_fill(0, 11, 'Task'),
        ];

        $response = $this->postJson('/api/routines', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Test Routine');
    }

    /**
     * freeユーザーは10件のルーティンを作成済みの場合、11件目の作成が拒否されることを確認するテスト。
     */
    public function test_free_plan_rejects_more_than_ten_routines(): void
    {
        $user = User::factory()->create(['plan' => 'free']);
        Sanctum::actingAs($user);

        // 10件のルーティンを作成
        for ($i = 1; $i <= 10; $i++) {
            \App\Models\Routine::factory()->create([
                'user_id' => $user->id,
                'title' => "Routine {$i}",
                'tasks' => ['Task 1'],
            ]);
        }

        // 11件目の作成を試みる
        $payload = [
            'title' => '11th Routine',
            'tasks' => ['Task 1'],
        ];

        $response = $this->postJson('/api/routines', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('message', '無料プランではタスクリストは10件までです。');
    }

    /**
     * unlimitedユーザーは10件を超えてもルーティンを作成できることを確認するテスト。
     */
    public function test_unlimited_plan_allows_more_than_ten_routines(): void
    {
        $user = User::factory()->create(['plan' => 'unlimited']);
        Sanctum::actingAs($user);

        // 10件のルーティンを作成
        for ($i = 1; $i <= 10; $i++) {
            \App\Models\Routine::factory()->create([
                'user_id' => $user->id,
                'title' => "Routine {$i}",
                'tasks' => ['Task 1'],
            ]);
        }

        // 11件目の作成を試みる（unlimitedなので成功する）
        $payload = [
            'title' => '11th Routine',
            'tasks' => ['Task 1'],
        ];

        $response = $this->postJson('/api/routines', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', '11th Routine');
    }
}

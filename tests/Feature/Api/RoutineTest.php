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
}

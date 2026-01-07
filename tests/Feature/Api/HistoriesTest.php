<?php

namespace Tests\Feature\Api;

use App\Models\History;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_histories_requires_authentication(): void
    {
        $this->getJson('/api/histories')->assertStatus(401);
    }

    public function test_histories_returns_only_own_histories(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $userHistory = History::factory()->for($user)->create([
            'title' => 'User History',
        ]);
        History::factory()->for($otherUser)->create([
            'title' => 'Other History',
        ]);

        $response = $this->actingAs($user)->getJson('/api/histories');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame($userHistory->id, $response->json('data.0.id'));
        $this->assertSame('User History', $response->json('data.0.title'));
    }

    public function test_histories_show_returns_history_detail(): void
    {
        $user = User::factory()->create();
        $history = History::factory()->for($user)->create([
            'tasks' => ['Task 1', 'Task 2'],
        ]);

        $response = $this->actingAs($user)->getJson("/api/histories/{$history->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $history->id)
            ->assertJsonPath('data.tasks.0', 'Task 1');
    }

    public function test_histories_show_hides_other_users_history(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $history = History::factory()->for($otherUser)->create();

        $this->actingAs($user)
            ->getJson("/api/histories/{$history->id}")
            ->assertStatus(404);
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\History;
use App\Models\Routine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_creates_history_with_started_at(): void
    {
        $user = User::factory()->create();
        $routine = Routine::factory()->for($user)->create();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/routines/{$routine->id}/start");

        $response->assertStatus(201)
            ->assertJsonPath('data.routine_id', $routine->id);

        $this->assertDatabaseHas('histories', [
            'user_id' => $user->id,
            'routine_id' => $routine->id,
        ]);

        $this->assertNotNull($response->json('data.started_at'));
    }

    public function test_complete_sets_finished_at_and_completed_true(): void
    {
        $user = User::factory()->create();
        $routine = Routine::factory()->for($user)->create();
        $history = History::factory()->create([
            'user_id' => $user->id,
            'routine_id' => $routine->id,
            'title' => $routine->title,
            'tasks' => $routine->tasks,
            'started_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/histories/{$history->id}/complete");

        $response->assertOk()
            ->assertJsonPath('data.completed', true);

        $this->assertNotNull($response->json('data.finished_at'));
    }

    public function test_abort_sets_finished_at_and_completed_false(): void
    {
        $user = User::factory()->create();
        $routine = Routine::factory()->for($user)->create();
        $history = History::factory()->create([
            'user_id' => $user->id,
            'routine_id' => $routine->id,
            'title' => $routine->title,
            'tasks' => $routine->tasks,
            'started_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/histories/{$history->id}/abort");

        $response->assertOk()
            ->assertJsonPath('data.completed', false);

        $this->assertNotNull($response->json('data.finished_at'));
    }
}

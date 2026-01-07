<?php

namespace Database\Factories;

use App\Models\History;
use App\Models\Routine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\History>
 */
class HistoryFactory extends Factory
{
    protected $model = History::class;

    public function definition(): array
    {
        return [
            'routine_id' => Routine::factory(),
            'user_id' => function (array $attributes) {
                $routine = Routine::find($attributes['routine_id']);

                return $routine?->user_id;
            },
            'title' => function (array $attributes) {
                $routine = Routine::find($attributes['routine_id']);

                return $routine?->title ?? fake()->sentence(3);
            },
            'tasks' => function (array $attributes) {
                $routine = Routine::find($attributes['routine_id']);

                return $routine?->tasks ?? ['Task 1', 'Task 2'];
            },
            'started_at' => now(),
            'finished_at' => null,
            'completed' => false,
        ];
    }
}

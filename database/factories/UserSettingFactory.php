<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserSetting>
 */
class UserSettingFactory extends Factory
{
    protected $model = UserSetting::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'theme' => 'light',
            'dark_mode' => 'system',
            'show_remaining_tasks' => false,
            'show_elapsed_time' => false,
            'show_celebration' => false,
        ];
    }
}

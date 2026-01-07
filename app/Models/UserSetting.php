<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'theme',
        'dark_mode',
        'show_remaining_tasks',
        'show_elapsed_time',
        'enable_task_estimated_time',
        'show_celebration',
    ];

    // ON/OFF系は boolean として扱う
    protected $casts = [
        'show_remaining_tasks' => 'boolean',
        'show_elapsed_time' => 'boolean',
        'enable_task_estimated_time' => 'boolean',
        'show_celebration' => 'boolean',
    ];

    // 所有ユーザー
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

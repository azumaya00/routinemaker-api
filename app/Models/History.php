<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'routine_id',
        'title',
        'tasks',
        'started_at',
        'finished_at',
        'completed',
    ];

    // 日時と boolean の型を整える
    protected $casts = [
        'tasks' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'completed' => 'boolean',
    ];

    // 所有ユーザー
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 元になったルーティン
    public function routine()
    {
        return $this->belongsTo(Routine::class);
    }
}

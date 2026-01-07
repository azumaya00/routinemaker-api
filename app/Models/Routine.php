<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Routine extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'tasks',
    ];

    // tasks は順序付き配列として扱う
    protected $casts = [
        'tasks' => 'array',
    ];

    // 所有ユーザー
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ルーティンに紐づく履歴
    public function histories()
    {
        return $this->hasMany(History::class);
    }
}

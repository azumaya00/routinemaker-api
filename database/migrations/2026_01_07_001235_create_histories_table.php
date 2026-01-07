<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('histories', function (Blueprint $table) {
            $table->id();
            // 実行したユーザー
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // 元になったルーティン
            $table->foreignId('routine_id')->constrained()->cascadeOnDelete();
            // 実行時のタイトル（スナップショット）
            $table->string('title');
            // 実行時のタスク配列（スナップショット）
            $table->json('tasks');
            // 開始時刻
            $table->timestamp('started_at');
            // 終了時刻（完了/中断）
            $table->timestamp('finished_at')->nullable();
            // 完了フラグ
            $table->boolean('completed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('histories');
    }
};

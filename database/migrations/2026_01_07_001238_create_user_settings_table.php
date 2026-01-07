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
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            // 1ユーザー1設定
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
            // テーマ（light / soft / dark）
            $table->string('theme')->default('light');
            // ダークモード（off / on / system）
            $table->string('dark_mode')->default('system');
            // 残りタスク数表示
            $table->boolean('show_remaining_tasks')->default(false);
            // 経過時間表示
            $table->boolean('show_elapsed_time')->default(false);
            // タスク目安時間の表示
            $table->boolean('enable_task_estimated_time')->default(false);
            // 完了演出の表示
            $table->boolean('show_celebration')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};

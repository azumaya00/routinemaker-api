<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * タスク目安時間機能は要件から削除されたため、enable_task_estimated_time カラムを削除する
     */
    public function up(): void
    {
        // カラムが存在する場合のみ削除（既存DB / 空DB どちらでも安全に動作）
        if (Schema::hasColumn('user_settings', 'enable_task_estimated_time')) {
            Schema::table('user_settings', function (Blueprint $table) {
                $table->dropColumn('enable_task_estimated_time');
            });
        }
    }

    /**
     * Reverse the migrations.
     * 
     * ロールバック時はカラムを復元する（既存データがあっても問題ないようにデフォルト値を設定）
     */
    public function down(): void
    {
        // カラムが存在しない場合のみ追加（重複エラーを防ぐ）
        if (!Schema::hasColumn('user_settings', 'enable_task_estimated_time')) {
            Schema::table('user_settings', function (Blueprint $table) {
                // カラムを復元（デフォルト値は false）
                $table->boolean('enable_task_estimated_time')->default(false)->after('show_elapsed_time');
            });
        }
    }
};

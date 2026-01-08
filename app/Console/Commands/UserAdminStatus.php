<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * ユーザーの管理者ステータスを確認するコマンド
 */
class UserAdminStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:admin-status {email : ステータスを確認するユーザーのemail}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '指定されたユーザーの管理者ステータスを確認する';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        // ユーザーを検索
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("ユーザーが見つかりません: {$email}");
            return self::FAILURE;
        }

        // 管理者ステータスを表示
        $status = $user->is_admin ? '管理者' : '一般ユーザー';
        $this->info("ユーザー: {$email} (ID: {$user->id})");
        $this->info("ステータス: {$status}");
        $this->info("プラン: {$user->plan}");

        return self::SUCCESS;
    }
}

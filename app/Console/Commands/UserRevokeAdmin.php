<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * ユーザーの管理者権限を剥奪するコマンド
 * 
 * 【危険操作】本番環境で実行する場合は、対象ユーザーのemailを正確に確認すること
 * 誤って管理者の権限を剥奪すると、管理機能にアクセスできなくなる
 */
class UserRevokeAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:revoke-admin {email : 管理者権限を剥奪するユーザーのemail}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '指定されたユーザーの管理者権限を剥奪する';

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

        // 既に非管理者の場合はメッセージを出して正常終了
        if (!$user->is_admin) {
            $this->info("ユーザーは既に非管理者です: {$email} (ID: {$user->id})");
            return self::SUCCESS;
        }

        // 管理者権限を剥奪
        $user->is_admin = false;
        $user->save();

        $this->info("ユーザーの管理者権限を剥奪しました: {$email} (ID: {$user->id})");

        return self::SUCCESS;
    }
}

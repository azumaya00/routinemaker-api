<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * ユーザーを管理者に昇格するコマンド
 * 
 * 【危険操作】本番環境で実行する場合は、対象ユーザーのemailを正確に確認すること
 * 誤って一般ユーザーを管理者に昇格させると、セキュリティリスクが発生する
 */
class UserMakeAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:make-admin {email : 管理者に昇格するユーザーのemail}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '指定されたユーザーを管理者に昇格させる';

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

        // 既に管理者の場合はメッセージを出して正常終了
        if ($user->is_admin) {
            $this->info("ユーザーは既に管理者です: {$email} (ID: {$user->id})");
            return self::SUCCESS;
        }

        // 管理者に昇格
        $user->is_admin = true;
        $user->save();

        $this->info("ユーザーを管理者に昇格しました: {$email} (ID: {$user->id})");

        return self::SUCCESS;
    }
}

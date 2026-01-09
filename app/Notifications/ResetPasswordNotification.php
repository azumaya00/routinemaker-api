<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends BaseResetPassword
{
    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $query = http_build_query([
            'token' => $this->token,
            'email' => $notifiable->email,
        ]);
        $resetUrl = "{$frontendUrl}/reset-password?{$query}";

        return (new MailMessage)
            ->subject('パスワード再設定のお知らせ')
            ->line('パスワード再設定のリクエストを受け付けました。')
            ->line('以下のボタンからパスワードを再設定してください。')
            ->action('パスワードを再設定する', $resetUrl)
            ->line('このリンクの有効期限は60分です。')
            ->line('心当たりがない場合は、このメールを無視してください。');
    }
}

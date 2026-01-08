<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 管理者専用ルートを保護するMiddleware
 * 
 * 認証済みユーザーの is_admin フラグをチェックし、
 * false の場合は 403 Forbidden を返す
 */
class EnsureAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 認証されていない場合は、auth middleware で既に弾かれているはず
        // 念のためチェック
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        // 管理者でない場合は 403 を返す
        if (!$user->is_admin) {
            abort(403, '管理者権限が必要です。');
        }

        return $next($request);
    }
}

<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// SPA の preflight が 302 にならないように明示的に許可する。
Route::options('/login', fn () => response()->noContent());
Route::options('/logout', fn () => response()->noContent());
Route::options('/register', fn () => response()->noContent());

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->name('login');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// SPA 向けの最小登録。登録後はセッションを作ってそのまま認証済みにする。
Route::post('/register', [\App\Http\Controllers\Auth\RegisteredUserController::class, 'store'])
    ->name('register');

// Google ログイン（Socialite）: SPA からのリダイレクト経由で実行する。
Route::prefix('api/auth/google')->group(function () {
    Route::get('/redirect', [GoogleAuthController::class, 'redirect']);
    Route::get('/callback', [GoogleAuthController::class, 'callback']);
});
